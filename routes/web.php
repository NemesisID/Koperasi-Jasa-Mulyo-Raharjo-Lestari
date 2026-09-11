<?php

use Illuminate\Support\Facades\Route;

// Web service murni: root diarahkan ke dokumentasi API (Scalar)
// Deployment subfolder /be: prefix /be agar tidak keluar dari folder backend.
Route::redirect('/', '/be/docs/api');
