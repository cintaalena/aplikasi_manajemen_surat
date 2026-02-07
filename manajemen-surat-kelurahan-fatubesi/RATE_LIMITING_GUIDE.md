# 🔒 Rate Limiting Quick Reference

## Endpoint Protection Summary

| Endpoint | Rate Limit | Window | Purpose |
|----------|-----------|--------|---------|
| `POST /register/request-otp` | 3 requests | 10 minutes | Prevent OTP email spam |
| `POST /register/verify-otp` | 5 attempts | 5 minutes | Prevent OTP brute force (IP-based) |
| OTP Verification (DB) | 5 attempts | Per OTP | Prevent OTP brute force (Email-based) |
| `POST /login` | 5 attempts | 1 minute | Prevent password brute force |
| `POST /api/letters/{slug}/finalize` | 10 requests | 1 minute | Prevent letter spam |

## Response Codes

- **200/302**: Success
- **422**: Validation failed (wrong password/OTP)
- **429**: Too Many Requests (rate limit exceeded)

## Response Headers

```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 2
Retry-After: 42
```

## OTP Attempt Counter

### Flow:
1. User requests OTP → `attempts = 0`
2. User enters wrong OTP → `attempts = 1`
3. User enters wrong OTP again → `attempts = 2`
4. ...
5. After 5 failed attempts → OTP blocked
6. User requests new OTP → `attempts = 0` (reset)

### Error Messages:
- Attempt 1-4: `"Kode OTP salah. Sisa percobaan: X"`
- Attempt 5+: `"Terlalu banyak percobaan gagal. Silakan minta OTP baru."`

## Testing Commands

### Bash/Linux:
```bash
bash scripts/test_rate_limiting.sh
```

### PowerShell/Windows:
```powershell
.\scripts\test_rate_limiting.ps1
```

### Manual cURL Test:
```bash
# Test OTP spam (expect 429 on 4th request)
for i in {1..4}; do
  curl -X POST http://localhost/register/request-otp \
    -H "Content-Type: application/json" \
    -d '{"name":"Test","email":"test@example.com","jabatan":"lurah","password":"pass123","password_confirmation":"pass123"}'
done
```

## Monitoring

### Check Logs:
```bash
# Tail logs
tail -f storage/logs/laravel.log

# Filter rate limit events
grep "Rate limit exceeded" storage/logs/laravel.log

# PowerShell
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

### Log Format (ThrottleWithLog):
```json
{
  "message": "Rate limit exceeded",
  "context": {
    "ip": "127.0.0.1",
    "url": "http://localhost/register/request-otp",
    "method": "POST",
    "user_agent": "Mozilla/5.0...",
    "user_id": null,
    "max_attempts": 3,
    "decay_minutes": 10
  }
}
```

## Configuration

### Change Limits (in routes files):

```php
// Syntax: throttle:maxAttempts,decayMinutes

// Very strict
->middleware('throttle:3,1')   // 3 per 1 minute

// Moderate
->middleware('throttle:10,5')  // 10 per 5 minutes

// Lenient
->middleware('throttle:60,1')  // 60 per 1 minute (Laravel default)
```

### Cache Driver
Rate limiting uses Laravel cache. For production:

**Option 1: Database (default)**
```env
CACHE_STORE=database
```

**Option 2: Redis (recommended for production)**
```env
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Production Considerations

### 1. Reverse Proxy / Load Balancer
If behind nginx/Apache/CloudFlare, configure trusted proxies:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

### 2. Custom Rate Limit Keys
Per-user instead of per-IP:

```php
// Custom throttle key
Route::post('/endpoint', [Controller::class, 'method'])
    ->middleware('throttle:10,1,user');
```

### 3. Whitelist IPs
Edit `app/Http/Middleware/ThrottleWithLog.php` to bypass certain IPs.

## Troubleshooting

### Issue: Rate limit not working
**Check:**
1. Cache is configured: `php artisan config:cache`
2. Cache table exists: `php artisan migrate`
3. Middleware is registered

### Issue: Too strict in development
**Solution:** Temporarily increase limits or disable:

```php
// For development only
if (app()->environment('local')) {
    // Remove throttle or increase limits
}
```

### Issue: 429 on every request
**Cause:** Cache not clearing properly  
**Fix:**
```bash
php artisan cache:clear
php artisan config:clear
```

## Security Best Practices

✅ **Do:**
- Monitor rate limit logs regularly
- Adjust limits based on legitimate usage patterns
- Use Redis in production for better performance
- Combine with CAPTCHA for critical endpoints
- Implement IP blocklist for repeated attackers

❌ **Don't:**
- Set limits too low (frustrate legitimate users)
- Disable rate limiting in production
- Use same key for all endpoints
- Forget to test after changes

## Next Steps

1. ✅ Apply this patch
2. ⬜ Test thoroughly in development
3. ⬜ Monitor logs for 1 week
4. ⬜ Adjust limits if needed
5. ⬜ Deploy to production
6. ⬜ Implement RBAC (next security patch)
