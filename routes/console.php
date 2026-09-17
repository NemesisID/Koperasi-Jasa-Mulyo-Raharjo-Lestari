<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// #15 — hold tagihan rutin Rp50.000 dari saldo anggota, awal setiap bulan.
Schedule::command('savings:hold-monthly')->monthlyOn(1, '01:00');
