<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pickup rutin bulanan: buat tiket jemput untuk semua anggota aktif (source='rutin').
// ponytail: endpoint /pickups/generate-routine juga bisa trigger manual.
Schedule::command('pickups:generate-routine')->monthlyOn(1, '06:00');

// Rutinan per-warga (hari + slot pagi/siang/sore): generate tiket untuk besok tiap pagi.
Schedule::command('pickups:generate-scheduled')->dailyAt('05:00');
