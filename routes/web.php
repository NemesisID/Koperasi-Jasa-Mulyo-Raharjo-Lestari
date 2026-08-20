<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Login'));
Route::get('/pengurus/{any?}', fn() => Inertia::render('Pengurus/Index'))->where('any', '.*');
Route::get('/anggota/{any?}', fn() => Inertia::render('Anggota/Index'))->where('any', '.*');
Route::get('/petugas/{any?}', fn() => Inertia::render('Petugas/Index'))->where('any', '.*');

require __DIR__.'/auth.php';

