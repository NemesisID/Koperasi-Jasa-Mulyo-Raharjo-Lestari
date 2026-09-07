<?php

use Illuminate\Support\Facades\Route;

// Web service murni: root diarahkan ke dokumentasi API (Scalar)
Route::redirect('/', '/docs/api');
