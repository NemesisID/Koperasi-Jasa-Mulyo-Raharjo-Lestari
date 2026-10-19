<?php

use Illuminate\Support\Facades\Route;

// Redirect root ke /be/docs/api
Route::redirect('/', '/be/docs/api');

// Dokumentasi API (Scalar) — dapat diakses di /be/docs/api maupun /docs/api
Route::view('/be/docs/api', 'docs.api')->name('docs.api.be');
Route::view('/docs/api', 'docs.api')->name('docs.api');

// Fallback untuk openapi.json jika diakses dari /be/docs/openapi.json atau relative dari /be/docs/api/openapi.json
Route::get('/be/docs/openapi.json', function () {
    return response()->file(public_path('docs/openapi.json'), ['Content-Type' => 'application/json']);
});
Route::get('/be/docs/api/openapi.json', function () {
    return response()->file(public_path('docs/openapi.json'), ['Content-Type' => 'application/json']);
});

