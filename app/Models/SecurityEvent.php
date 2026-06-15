<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY (A09 - Security Logging and Monitoring Failures)
 *
 * Centralized security event log. Append-only — never update or delete.
 * Records authentication failures, account lockouts, rate limit hits,
 * unauthorized access attempts, and suspicious file uploads.
 *
 * @property int         $id
 * @property string      $event_type
 * @property string      $severity
 * @property int|null    $user_id
 * @property string|null $username_attempted
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property array|null  $context
 * @property \Carbon\Carbon $created_at
 */
class SecurityEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'event_type',
        'severity',
        'user_id',
        'username_attempted',
        'ip_address',
        'user_agent',
        'url',
        'context',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
    ];

    const EVENT_LOGIN_FAILED        = 'login_failed';
    const EVENT_LOGIN_SUCCESS       = 'login_success';
    const EVENT_ACCOUNT_LOCKED      = 'account_locked';
    const EVENT_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    const EVENT_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const EVENT_SUSPICIOUS_UPLOAD   = 'suspicious_upload';
    const EVENT_CSRF_MISMATCH       = 'csrf_mismatch';
    const EVENT_SESSION_HIJACK      = 'session_hijack_attempt';

    const SEVERITY_INFO     = 'info';
    const SEVERITY_WARNING  = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Record a security event. Never throws — logs to file if DB write fails.
     */
    public static function record(
        string  $eventType,
        string  $severity = self::SEVERITY_WARNING,
        ?int    $userId = null,
        ?string $usernameAttempted = null,
        array   $context = []
    ): void {
        try {
            $request = request();

            self::create([
                'event_type'          => $eventType,
                'severity'            => $severity,
                'user_id'             => $userId,
                'username_attempted'  => $usernameAttempted ? substr($usernameAttempted, 0, 255) : null,
                'ip_address'          => $request->ip(),
                'user_agent'          => substr($request->userAgent() ?? '', 0, 512),
                'url'                 => substr($request->fullUrl(), 0, 512),
                'context'             => $context ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::error('SecurityEvent DB write failed', [
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
                'context'    => $context,
            ]);
        }
    }
}
