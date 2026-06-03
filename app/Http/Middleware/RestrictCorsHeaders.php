<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictCorsHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Patch hanya untuk endpoint API agar tidak mengganggu halaman web/Inertia.
        if (! $request->is('api/*')) {
            return $response;
        }

        $allowedOrigins = array_values(array_filter(array_map('trim', explode(',', env(
            'CORS_ALLOWED_ORIGINS',
            'http://127.0.0.1:8000,http://localhost:8000,http://127.0.0.1:5173,http://localhost:5173,http://127.0.0.1:5174,http://localhost:5174'
        )))));

        $origin = $request->headers->get('Origin');

        // Hapus dulu header CORS bawaan agar tidak tersisa Access-Control-Allow-Origin: *
        $response->headers->remove('Access-Control-Allow-Origin');
        $response->headers->remove('Access-Control-Allow-Credentials');
        $response->headers->remove('Access-Control-Allow-Methods');
        $response->headers->remove('Access-Control-Allow-Headers');
        $response->headers->remove('Access-Control-Max-Age');

        // Jika request tidak punya Origin, tidak perlu kirim header CORS.
        if (! $origin) {
            return $response;
        }

        // Jika Origin tidak termasuk whitelist, jangan beri CORS.
        if (! in_array($origin, $allowedOrigins, true)) {
            return $response;
        }

        // Jika Origin valid, pantulkan origin tersebut, bukan wildcard "*".
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Vary', 'Origin', false);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            'Accept, Authorization, Content-Type, Origin, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN'
        );

        return $response;
    }
}