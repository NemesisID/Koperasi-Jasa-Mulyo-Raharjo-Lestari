<?php

namespace App\Console\Commands;

use App\Models\Pickup;
use App\Models\PickupSchedule;
use App\Services\TrashWeighingService;
use Illuminate\Console\Command;

class GenerateScheduledPickups extends Command
{
    protected $signature = 'pickups:generate-scheduled';

    protected $description = 'Buat tiket jemput dari rutinan per-warga (hari + slot pagi/siang/sore) untuk besok';

    /** Slot → jam jemput. */
    private const SLOT_HOURS = ['pagi' => 7, 'siang' => 12, 'sore' => 16];

    public function handle(TrashWeighingService $weighing): int
    {
        $target = now()->addDay()->startOfDay();
        $created = 0;

        foreach (PickupSchedule::with('member')->where('is_active', true)->get() as $schedule) {
            if ($schedule->member?->status !== 'aktif' || ! in_array($target->dayOfWeek, $schedule->days ?? [])) {
                continue;
            }

            foreach ($schedule->slots ?? [] as $slot) {
                $at = $target->copy()->setTime(self::SLOT_HOURS[$slot] ?? 7, 0);
                // ponytail: dedupe query per slot — index gabungan member+source+tanggal kalau skala membesar
                $exists = Pickup::where('member_id', $schedule->member_id)
                    ->where('source', 'rutin')
                    ->whereBetween('scheduled_at', [$at->copy()->startOfHour(), $at->copy()->endOfHour()])
                    ->exists();
                if ($exists) {
                    continue;
                }

                $weighing->createTicket([
                    'member_id' => $schedule->member_id,
                    'location_type' => 'jemput_rumah',
                    'source' => 'rutin',
                    'scheduled_at' => $at,
                    'notes' => 'Penjemputan rutin ('.$slot.')',
                ], $schedule->member->user);
                $created++;
            }
        }

        $this->info("{$created} tiket rutinan per-warga dibuat untuk {$target->toDateString()}.");

        return self::SUCCESS;
    }
}
