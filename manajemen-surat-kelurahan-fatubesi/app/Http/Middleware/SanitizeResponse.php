<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SanitizeResponse
{
    /**
     * SECURITY: Sanitize response data to prevent sensitive data exposure
     * - Remove password fields
     * - Remove API tokens
     * - Mask credential codes
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only sanitize JSON responses
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            if (is_array($data)) {
                $sanitized = $this->sanitizeArray($data);
                $response->setData($sanitized);
            }
        }

        return $response;
    }

    /**
     * Recursively sanitize array data
     */
    private function sanitizeArray(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_hash',
            'api_token',
            'api_secret',
            'secret_key',
            'private_key',
            'access_token',
            'refresh_token',
            'credential_code_hash', // Our custom field
            'otp_hash',
            'remember_token',
        ];

        foreach ($data as $key => &$value) {
            // Remove sensitive keys completely
            if (in_array($key, $sensitiveKeys, true)) {
                unset($data[$key]);
                continue;
            }

            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $value = $this->sanitizeArray($value);
            }
        }

        return $data;
    }
}
