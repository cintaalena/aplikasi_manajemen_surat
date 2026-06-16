<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Vite;

class SecurityHeaders
{
    /**
     * SECURITY: Add comprehensive security headers to all responses
     * Protects against: XSS, Clickjacking, MIME sniffing, etc.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        Vite::useCspNonce($nonce);
        view()->share('cspNonce', $nonce);
        $response = $next($request);
        return self::applyToResponse($response, $nonce);
    }

         public static function applyToResponse(Response $response, ?string $nonce = null): Response
    {
        $nonce = $nonce ?: base64_encode(random_bytes(16));
        $viteDevServer = config('app.env') === 'local' 
            ? ' http://localhost:5173 http://localhost:5174 http://127.0.0.1:5173 http://127.0.0.1:5174 ws://localhost:5173 ws://localhost:5174 ws://127.0.0.1:5173 ws://127.0.0.1:5174'
            : '';
        
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'" . $viteDevServer,
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net" . $viteDevServer,
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self'" . $viteDevServer,
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];

        if (config('app.env') !== 'local') {
            $csp[] = "upgrade-insecure-requests";
        }

        $contentType = $response->headers->get('Content-Type', '');
        $isHtml = str_contains($contentType, 'text/html');

        if ($isHtml) {
            $response->headers->set('Content-Security-Policy', implode('; ', $csp));
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        $response->headers->set('X-XSS-Protection', '1; mode=block');

        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $permissions = [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()',
        ];
        $response->headers->set('Permissions-Policy', implode(', ', $permissions));

        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
