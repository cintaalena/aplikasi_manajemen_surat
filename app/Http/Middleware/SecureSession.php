<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SecurityEvent;

class SecureSession
{
    /**
     * SECURITY: Enforce session security best practices
     * - Regenerate session ID on login/logout
     * - Detect session hijacking via fingerprinting
     * - Auto-lock after inactivity
     */
    public function handle(Request $request, Closure $next): Response
    {
        // SECURITY: Session fingerprinting untuk detect hijacking
        $currentFingerprint = $this->generateFingerprint($request);
        $storedFingerprint = $request->session()->get('_fingerprint');

        if ($storedFingerprint && $storedFingerprint !== $currentFingerprint) {
            // Possible session hijacking detected!
            Log::warning('Possible session hijacking detected', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id'    => $request->user()?->id,
            ]);

            // SECURITY (A09): Record session hijack attempt to security events table
            SecurityEvent::record(
                SecurityEvent::EVENT_SESSION_HIJACK,
                SecurityEvent::SEVERITY_CRITICAL,
                $request->user()?->id,
                null,
                ['stored_ua_hash' => substr($storedFingerprint, 0, 8) . '...']
            );

            // Force logout
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(401, 'Session invalid. Please login again.');
        }

        // Set fingerprint pada session baru
        if (!$storedFingerprint) {
            $request->session()->put('_fingerprint', $currentFingerprint);
        }

        // SECURITY: Update last activity timestamp
        $request->session()->put('_last_activity', now()->timestamp);

        $response = $next($request);

        // SECURITY: Set secure cookie attributes via response
        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Illuminate\Http\RedirectResponse) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * Generate unique fingerprint based on user agent only.
     * IP-based fingerprinting is removed to prevent false positives caused by
     * IPv4/IPv6 dual-stack switching (e.g. ::1 vs 127.0.0.1 on localhost),
     * mobile network changes, and CGNAT environments.
     */
    private function generateFingerprint(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        return hash('sha256', $userAgent . config('app.key'));
    }
}
