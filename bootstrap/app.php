<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Helpers\ApiResponse;
use App\Http\Middleware\ForceJsonHeader;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        // web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ForceJsonHeader::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            return ApiResponse::error('Unauthenticated', 401);
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return ApiResponse::error('Endpoint not found', 404);
        });

        $exceptions->render(function (Throwable $e, $request) {
            // Kalau APP_DEBUG = true → tampilkan pesan asli (dev mode)
            if (config('app.debug')) {
                return \App\Helpers\ApiResponse::error(
                    $e->getMessage(),
                    500,
                    [
                        'exception' => get_class($e),
                        // 'trace'     => $e->getTrace()
                    ]
                );
            }

            // Production mode → pesan umum
            return \App\Helpers\ApiResponse::error(
                'Internal server error',
                500
            );
        });
    })->create();