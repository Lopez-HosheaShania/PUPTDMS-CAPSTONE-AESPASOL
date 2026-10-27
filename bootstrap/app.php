<?php

use App\Helpers\AuditLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApplySystemModes;
use App\Http\Middleware\EnsureSessionActivity;
use App\Http\Middleware\LogFailedHttpResponses;
use App\Http\Middleware\ValidateFormInput;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withMiddleware(function ($middleware) {
        $middleware->append([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ]);

        $middleware->web(append: [
            ValidateFormInput::class,
            ApplySystemModes::class,
            EnsureSessionActivity::class,
            LogFailedHttpResponses::class,
        ], remove: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'admin_current_password']);

        $exceptions->respond(function ($response, \Throwable $exception, $request) {
            AuditLogger::logException($exception, 'system', $response->getStatusCode());

            if ($response->getStatusCode() >= 500) {
                $status = $response->getStatusCode();
                $message = $status === 503
                    ? 'The system is temporarily unavailable. Please try again later.'
                    : 'Something went wrong. Please try again later.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], $status);
                }

                return response()->view($status === 503 ? 'errors.503' : 'errors.500', [], $status);
            }

            return $response;
        });
    })
    ->create();
