<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // State-less REST API
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // Memastikan respons error dikembalikan dalam format JSON jika request adalah API / expects JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->wantsJson() || $request->expectsJson();
        });

        // 1. Handling Validasi Input (Status 422)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 2. Handling Data Tidak Ditemukan / Route Not Found (Status 404)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson() || $request->expectsJson()) {
                $message = $e->getPrevious() instanceof ModelNotFoundException
                    ? 'Resource not found.'
                    : 'Endpoint or route not found.';

                return response()->json([
                    'message' => $message,
                    'errors'  => null,
                ], 404);
            }
        });

        // 3. Handling Autentikasi / Token Invalid (Status 401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated or invalid token.',
                    'errors'  => null,
                ], 401);
            }
        });

        // 4. Handling Method HTTP Tidak Diizinkan (Status 405)
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'HTTP method is not supported for this route.',
                    'errors'  => null,
                ], 405);
            }
        });

        // 5. Handling Akses Ditolak / Otorisasi Role (Status 403)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Akses ditolak.',
                    'errors'  => null,
                ], 403);
            }
        });

        // 6. Handling Unhandled Internal Server Error (Status 500)
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson() || $request->expectsJson()) {
                $message = config('app.debug') 
                    ? $e->getMessage() 
                    : 'Internal server error. Please try again later.';

                return response()->json([
                    'message' => $message,
                    'errors'  => config('app.debug') ? [
                        'exception' => get_class($e),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                    ] : null,
                ], 500);
            }
        });

    })->create();
