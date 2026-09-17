<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Deployment subfolder /be: URL /be/... di-rewrite ke public/ tanpa mengubah
// REQUEST_URI, jadi Laravel mencocokkan route dengan prefix /be yang tidak ada.
// Strip prefix agar routing melihat path relatif (/api/v1/...).
$uri = $_SERVER['REQUEST_URI'] ?? '';
if ($uri === '/be' || str_starts_with($uri, '/be/')) {
    $_SERVER['REQUEST_URI'] = substr($uri, 3);
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
