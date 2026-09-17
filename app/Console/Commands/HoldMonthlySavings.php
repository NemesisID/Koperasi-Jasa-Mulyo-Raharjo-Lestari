<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use App\Services\SavingsService;
use Illuminate\Console\Command;

/**
 * #15 — hold tagihan rutin Rp50.000 dari saldo anggota tiap bulan.
 *
 * Dijadwalkan awal bulan (lihat routes/console.php). Aman dijalankan berulang:
 * potongan dan hitungan tunggakan sama-sama idempoten.
 */
class HoldMonthlySavings extends Command
{
    protected $signature = 'savings:hold-monthly';

    protected $description = 'Potong tagihan rutin Rp50.000 dari saldo anggota; nonaktifkan setelah 3 bulan berturut-turut gagal';

    public function handle(SavingsService $savingsService): int
    {
        // Jurnal kas wajib punya `handled_by`. Potongan ini aksi sistem, jadi
        // pakai pengurus terlama sebagai penanggung jawab.
        // ponytail: ganti ke akun sistem kalau audit menuntut pelaku non-manusia.
        $handler = User::where('role', 'pengurus')->orderBy('id')->first();

        if ($handler === null) {
            $this->error('Tidak ada user berperan pengurus — jurnal kas tidak dapat dicatat.');

            return self::FAILURE;
        }

        $held = 0;
        $insufficient = 0;
        $deactivated = 0;

        Member::where('status', 'aktif')->chunkById(100, function ($members) use ($savingsService, $handler, &$held, &$insufficient, &$deactivated): void {
            foreach ($members as $member) {
                $result = $savingsService->holdMonthlyWajib($member, $handler);

                if ($result['held']) {
                    $held++;
                } elseif ($result['reason'] === 'saldo_kurang') {
                    $insufficient++;
                } elseif ($result['reason'] === 'saldo_kurang_dinonaktifkan') {
                    $insufficient++;
                    $deactivated++;
                }
            }
        });

        $this->info("Hold bulanan selesai: {$held} terpotong, {$insufficient} saldo kurang, {$deactivated} dinonaktifkan.");

        return self::SUCCESS;
    }
}
