<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Pickup;
use App\Services\TrashWeighingService;
use Illuminate\Console\Command;

/**
 * Tiket penjemputan harian otomatis untuk semua anggota aktif.
 *
 * Satu baris `members` = satu alamat (rumah/pasar), jadi anggota dengan dua
 * tempat dapat dua tiket per hari. Petugasnya mengikuti plotting anggota
 * (members.officer_id), bukan dipilih ulang di sini.
 *
 * Dijadwalkan pagi (lihat routes/console.php). Aman dijalankan berulang:
 * alamat yang sudah punya tiket di tanggal itu dilewati.
 */
class GenerateDailyPickups extends Command
{
    protected $signature = 'pickups:generate-daily';

    protected $description = 'Buat tiket penjemputan harian untuk seluruh anggota aktif (sesuai plotting petugas)';

    public function handle(TrashWeighingService $weighingService): int
    {
        $date = now()->toDateString();
        $created = 0;
        $skipped = 0;

        Member::where('status', 'aktif')
            ->with('category:id,name')
            ->chunkById(200, function ($members) use ($date, $weighingService, &$created, &$skipped): void {
                foreach ($members as $member) {
                    // Alamat ini sudah punya tiket hari ini (termasuk permintaan
                    // manual dari anggota) → jangan dobel.
                    $exists = Pickup::where('member_id', $member->id)
                        ->whereDate('scheduled_at', $date)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    $weighingService->createDailyTicket($member, $date);
                    $created++;
                }
            });

        $this->info("Tiket harian {$date}: {$created} dibuat, {$skipped} dilewati (sudah ada).");

        return self::SUCCESS;
    }
}
