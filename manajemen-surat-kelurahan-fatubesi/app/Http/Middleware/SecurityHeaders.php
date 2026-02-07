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
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Inertia needs unsafe-inline
            "style-src 'self' 'unsafe-inline'", // Tailwind needs unsafe-inline
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'none'", // Prevent clickjacking
            "base-uri 'self'",
            "form-action 'self'",
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
