<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * SECURITY: Add comprehensive security headers to all responses
     * Protects against: XSS, Clickjacking, MIME sniffing, etc.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // SECURITY: Content Security Policy (CSP)
        // Prevent XSS by restricting resource loading
        $viteDevServer = config('app.env') === 'local' 
            ? ' http://localhost:5173 http://localhost:5174 http://127.0.0.1:5173 http://127.0.0.1:5174 ws://localhost:5173 ws://localhost:5174 ws://127.0.0.1:5173 ws://127.0.0.1:5174'
            : '';
        
        $csp = [
            "default-src 'self'",
            // SECURITY (A05): 'unsafe-eval' removed — it enables JS eval() which
            // is exploitable by XSS. Inertia + Vue 3 does NOT require eval().
            // 'unsafe-inline' is retained for Vue SFC style injection (Tailwind).
            "script-src 'self' 'unsafe-inline'" . $viteDevServer,
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net" . $viteDevServer, // Tailwind needs unsafe-inline
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self'" . $viteDevServer,
            "frame-ancestors 'none'", // Prevent clickjacking
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",  // SECURITY (A05): Block Flash/plugins
            "upgrade-insecure-requests", // SECURITY (A05): Force HTTPS sub-resources
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // SECURITY: Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');

        // SECURITY: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // SECURITY: Enable XSS protection in older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // SECURITY: Referrer policy - don't leak URLs to external sites
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // SECURITY: Permissions policy (formerly Feature Policy)
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

        // SECURITY: HSTS - Force HTTPS (only in production)
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
