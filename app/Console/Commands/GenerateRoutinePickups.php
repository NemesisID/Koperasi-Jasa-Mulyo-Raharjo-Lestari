<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\TrashWeighingService;
use Illuminate\Console\Command;

class GenerateRoutinePickups extends Command
{
    protected $signature = 'pickups:generate-routine';

    protected $description = 'Buat tiket penjemputan rutin bulanan untuk semua anggota aktif (source=rutin)';

    public function handle(TrashWeighingService $weighing): int
    {
        $members = Member::where('status', 'aktif')->get();
        $created = 0;

        foreach ($members as $member) {
            $weighing->createTicket([
                'member_id' => $member->id,
                'location_type' => 'jemput_rumah',
                'source' => 'rutin',
                'scheduled_at' => now()->addDays(1),
            ], $member->user);
            $created++;
        }

        $this->info("{$created} tiket rutin dibuat.");

        return self::SUCCESS;
    }
}
