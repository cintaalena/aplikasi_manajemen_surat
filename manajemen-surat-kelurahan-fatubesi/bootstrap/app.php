<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecureSession::class, // SECURITY: Session hijacking protection
            \App\Http\Middleware\SecurityHeaders::class, // SECURITY: Security headers (CSP, XSS, etc)
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SanitizeResponse::class, // SECURITY: Prevent sensitive data leakage
        ]);

        // SECURITY: File upload validation middleware
        $middleware->alias([
            'secure.upload' => \App\Http\Middleware\SecureFileUpload::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
