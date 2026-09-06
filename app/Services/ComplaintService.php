<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Complaint;
use App\Models\User;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    /**
     * Batas waktu komplain setelah nota timbang selesai.
     */
    private const MAX_COMPLAINT_HOURS = 72; // 3x24 jam

    public function __construct(
        private readonly ComplaintRepositoryInterface $complaintRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * @param  array{pickup_id: int, issue_type: string, description: string, proof_image?: string}  $data
     */
    public function submitComplaint(User $submitter, array $data): Complaint
    {
        $pickup = \App\Models\Pickup::findOrFail($data['pickup_id']);

        if ($pickup->status !== 'selesai') {
            throw new BusinessLogicException('Komplain hanya dapat diajukan untuk nota timbang berstatus selesai.');
        }

        // Anggota hanya boleh mengkomplain nota miliknya sendiri
        if ($submitter->role === 'anggota' && $pickup->member?->user_id !== $submitter->id) {
            throw new BusinessLogicException('Anda hanya dapat mengajukan komplain untuk nota timbang Anda sendiri.');
        }

        // Batas waktu 3x24 jam dari penyelesaian timbang
        if ($pickup->completed_at && $pickup->completed_at->diffInHours(now()) > self::MAX_COMPLAINT_HOURS) {
            throw new BusinessLogicException('Batas waktu komplain 3x24 jam sejak nota terbit telah terlewati.');
        }

        $existing = Complaint::where('pickup_id', $pickup->id)
            ->whereNotIn('status', ['ditolak'])
            ->exists();

        if ($existing) {
            throw new BusinessLogicException('Komplain untuk nota timbang ini sudah diajukan sebelumnya.');
        }

        return $this->complaintRepository->create([
            'pickup_id' => $pickup->id,
            'member_id' => $pickup->member_id,
            'issue_type' => $data['issue_type'],
            'description' => $data['description'],
            'proof_image' => $data['proof_image'] ?? null,
            'status' => 'diajukan',
        ]);
    }

    /**
     * Selesaikan komplain: diterima -> catat transaksi penyesuaian kas (expense)
     * agar saldo terkoreksi; ditolak -> cukup catat alasan.
     *
     * @param  array{status: string, adjustment_amount?: float, resolution_note: string}  $resolutionData
     */
    public function resolveComplaint(int $complaintId, array $resolutionData, User $resolver): Complaint
    {
        return DB::transaction(function () use ($complaintId, $resolutionData, $resolver): Complaint {
            $complaint = Complaint::lockForUpdate()->findOrFail($complaintId);

            if (in_array($complaint->status, ['diterima', 'ditolak'])) {
                throw new BusinessLogicException("Komplain ini sudah diselesaikan dengan status '{$complaint->status}'.");
            }

            $adjustmentAmount = (float) ($resolutionData['adjustment_amount'] ?? 0);

            if ($resolutionData['status'] === 'diterima' && $adjustmentAmount > 0) {
                // Jurnal penyesuaian: pengeluaran kas koreksi ke anggota
                $adjustmentCategoryId = $this->transactionRepository->findCategoryIdByName('Pencairan Saldo Sampah / Cashout');
                $this->transactionRepository->create([
                    'member_id' => $complaint->member_id,
                    'category_id' => $adjustmentCategoryId,
                    'type' => 'expense',
                    'amount' => $adjustmentAmount,
                    'description' => "Penyesuaian komplain #{$complaint->id} ({$complaint->issue_type})",
                    'payment_method' => 'tunai', // enum terbatas: penyesuaian dibayar dari kas
                    'status' => 'berhasil',
                    'handled_by' => $resolver->id,
                ]);
            }

            return $this->complaintRepository->update($complaint->id, [
                'status' => $resolutionData['status'],
                'adjustment_amount' => $adjustmentAmount,
                'resolution_note' => $resolutionData['resolution_note'],
                'resolved_by' => $resolver->id,
                'resolved_at' => now(),
            ]);
        });
    }

    public function getComplaints(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->complaintRepository->paginate($filters);
    }

    public function getComplaint(int $id): Complaint
    {
        return $this->complaintRepository->findById($id);
    }
}
