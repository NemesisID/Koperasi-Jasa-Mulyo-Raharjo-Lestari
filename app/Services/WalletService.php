<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Complaint;
use App\Models\Pickup;
use App\Models\ShuMember;
use App\Models\User;
use App\Models\WithdrawRequest;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * Saldo dompet anggota — diturunkan dari mutasi (tanpa kolom saldo; hitung ulang tiap request).
     * ponytail: derived balance, O(jumlah mutasi) per anggota — pindah ke kolom saldo + trigger
     * jika volume transaksi anggota membesar.
     */
    public function getMemberWalletSummary(int $memberId): array
    {
        $member = \App\Models\Member::with('user:id,username')->findOrFail($memberId);

        $fromTrash = (float) Pickup::where('member_id', $memberId)
            ->where('status', 'selesai')->sum('total_net');

        $fromComplaint = (float) Complaint::where('member_id', $memberId)
            ->where('status', 'diterima')->sum('adjustment_amount');

        $fromShu = (float) ShuMember::where('member_id', $memberId)
            ->where('status', 'sudah_dibagikan')->sum('total_shu');

        $withdrawn = (float) WithdrawRequest::where('member_id', $memberId)
            ->where('status', 'disetujui')->sum('amount');

        $pendingWithdraw = (float) WithdrawRequest::where('member_id', $memberId)
            ->where('status', 'pending')->sum('amount');

        return [
            'member' => [
                'id' => $member->id,
                'member_code' => $member->member_code,
                'name' => $member->name,
            ],
            'current_balance' => round($fromTrash + $fromComplaint + $fromShu - $withdrawn, 2),
            'total_earned' => round($fromTrash + $fromComplaint + $fromShu, 2),
            'total_withdrawn' => $withdrawn,
            'pending_withdrawal' => $pendingWithdraw,
            'available_balance' => round($fromTrash + $fromComplaint + $fromShu - $withdrawn - $pendingWithdraw, 2),
            // Breakdown sumber saldo: sampah vs SHU (untuk dashboard anggota).
            'balance_from_trash' => round($fromTrash + $fromComplaint - $withdrawn, 2),
            'balance_from_shu' => $fromShu,
        ];
    }

    /**
     * Riwayat mutasi dompet (masuk: sampah/SHU/penyesuaian; keluar: penarikan).
     */
    public function getMemberMutations(int $memberId): Collection
    {
        $mutations = collect();

        Pickup::where('member_id', $memberId)->where('status', 'selesai')
            ->with('items.category:id,name')
            ->orderByDesc('completed_at')->get()
            ->each(fn (Pickup $p) => $mutations->push([
                'type' => 'in',
                'source' => 'setor_sampah',
                'reference' => "Pickup #{$p->id}",
                'description' => 'Setoran sampah (net after potongan 20%)',
                'amount' => (float) $p->total_net,
                'date' => $p->completed_at?->toIso8601String(),
            ]));

        Complaint::where('member_id', $memberId)->where('status', 'diterima')
            ->where('adjustment_amount', '>', 0)->orderByDesc('resolved_at')->get()
            ->each(fn (Complaint $c) => $mutations->push([
                'type' => 'in',
                'source' => 'penyesuaian_komplain',
                'reference' => "Komplain #{$c->id}",
                'description' => "Penyesuaian {$c->issue_type}",
                'amount' => (float) $c->adjustment_amount,
                'date' => $c->resolved_at?->toIso8601String(),
            ]));

        ShuMember::with('distribution:id,created_at')
            ->where('member_id', $memberId)->where('status', 'sudah_dibagikan')->get()
            ->each(fn (ShuMember $s) => $mutations->push([
                'type' => 'in',
                'source' => 'shu',
                'reference' => "SHU #{$s->shu_distribution_id}",
                'description' => 'Dividen SHU tahunan',
                'amount' => (float) $s->total_shu,
                'date' => $s->paid_at?->toIso8601String() ?? $s->distribution?->created_at?->toIso8601String(),
            ]));

        WithdrawRequest::where('member_id', $memberId)->whereIn('status', ['disetujui', 'pending'])
            ->orderByDesc('created_at')->get()
            ->each(fn (WithdrawRequest $w) => $mutations->push([
                'type' => 'out',
                'source' => 'penarikan',
                'reference' => "Withdraw #{$w->id}",
                'description' => $w->status === 'pending' ? 'Pengajuan penarikan (menunggu)' : "Penarikan {$w->method}",
                'amount' => (float) $w->amount,
                'date' => $w->created_at?->toIso8601String(),
            ]));

        return $mutations->sortByDesc('date')->values();
    }

    /**
     * Ajukan penarikan: saldo tersedia harus mencukupi.
     */
    public function requestWithdraw(User $member, array $data): WithdrawRequest
    {
        $memberRow = $member->member;
        abort_if($memberRow === null, 403, 'Hanya anggota yang memiliki dompet saldo.');

        $summary = $this->getMemberWalletSummary($memberRow->id);

        if ($data['amount'] > $summary['available_balance']) {
            throw new BusinessLogicException(
                "Saldo tidak mencukupi. Saldo tersedia Rp".number_format($summary['available_balance'], 0, ',', '.')
                .", diminta Rp".number_format($data['amount'], 0, ',', '.').'.'
            );
        }

        return WithdrawRequest::create([
            'member_id' => $memberRow->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'account_holder' => $data['account_holder'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Proses approval: approve → jurnal pengeluaran kas + status disetujui;
     * reject → status ditolak (saldo tidak tersentuh).
     */
    public function processWithdrawApproval(int $requestId, array $approvalData, User $approver): WithdrawRequest
    {
        return DB::transaction(function () use ($requestId, $approvalData, $approver): WithdrawRequest {
            $withdraw = WithdrawRequest::lockForUpdate()->findOrFail($requestId);

            if ($withdraw->status !== 'pending') {
                throw new BusinessLogicException("Permintaan penarikan ini sudah diproses ({$withdraw->status}).");
            }

            if ($approvalData['action'] === 'approve') {
                $cashoutCategoryId = $this->transactionRepository->findCategoryIdByName('Pencairan Saldo Sampah / Cashout');
                $this->transactionRepository->create([
                    'member_id' => $withdraw->member_id,
                    'category_id' => $cashoutCategoryId,
                    'type' => 'expense',
                    'amount' => $withdraw->amount,
                    'description' => "Penarikan saldo anggota (Withdraw #{$withdraw->id}, {$withdraw->method})",
                    'payment_method' => $withdraw->method === 'transfer' ? 'transfer' : 'tunai',
                    'status' => 'berhasil',
                    'handled_by' => $approver->id,
                ]);
            }

            $withdraw->update([
                'status' => $approvalData['action'] === 'approve' ? 'disetujui' : 'ditolak',
                'proof_file' => $approvalData['proof_file'] ?? null,
                'notes' => $approvalData['notes'] ?? null,
                'processed_by' => $approver->id,
                'processed_at' => now(),
            ]);

            return $withdraw->fresh();
        });
    }
}
