# 🛡️ OTP COOLDOWN - Quick Guide

## Progressive Cooldown Rules

| Consecutive Failures | Cooldown Duration |
|---------------------|-------------------|
| 1 kali gagal | Tidak ada lock |
| 2 kali gagal | Lock 1 menit |
| 3 kali gagal | Lock 5 menit |
| 4+ kali gagal | Lock 15 menit |

## Triple Protection

```
┌─────────────────────────────────────────────┐
│  LAYER 1: Rate Limiting                     │
│  - OTP Request: 3 per 10 menit              │
│  - OTP Verify: 5 per 5 menit                │
├─────────────────────────────────────────────┤
│  LAYER 2: Attempt Counter                   │
│  - Max 5 percobaan per OTP                  │
│  - Setelah 5 gagal → Harus request OTP baru │
├─────────────────────────────────────────────┤
│  LAYER 3: Progressive Cooldown ⭐ NEW        │
│  - 2 consecutive failures → 1 min lock      │
│  - 3 consecutive failures → 5 min lock      │
│  - 4+ consecutive failures → 15 min lock    │
└─────────────────────────────────────────────┘
```

## User Flow

### Scenario: User Salah Input OTP

```
Attempt 1: Wrong OTP
→ Response: "Kode OTP salah. Sisa percobaan: 4"
→ Status: Bisa coba lagi

Attempt 2: Wrong OTP again
→ Response: "Kode OTP salah. Sisa percobaan: 3. Akun dikunci selama 1 menit."
→ Status: LOCKED for 1 minute

Attempt 3 (dalam 1 menit): Wrong OTP
→ Response: "Terlalu banyak percobaan gagal. Silakan coba lagi dalam 1 menit."
→ Status: 429 Too Many Requests

[Wait 1 minute]

Attempt 4 (setelah 1 menit): Correct OTP
→ Response: Success! Email verified
→ Status: consecutive_failures = 0, locked_until = null
```

### Scenario: Attacker Brute Force

```
Attempt 1: Wrong → OK (1s)
Attempt 2: Wrong → LOCKED 1 min (2s)
Attempt 3: 429 (wait 60s)
Attempt 4: 429 (wait)
Attempt 5: 429 (wait)

Total time for 5 attempts: 60+ seconds
vs Before: 5 seconds
```

## Testing

```powershell
# Test progressive cooldown
.\test_otp_cooldown.ps1

# Expected Results:
# TEST 1: Attempt 1-2 FAILED, Attempt 3 LOCKED (1 min)
# TEST 2: Attempt 1-2 FAILED, Attempt 3-4 LOCKED (5 min logic, tapi kena 1 min dari test sebelumnya)
# TEST 3: Attempt 1-2 FAILED, Attempt 3-5 LOCKED (15 min logic)
```

## Database Fields

```sql
-- New fields in email_otps table:
locked_until          TIMESTAMP NULL      -- Sampai kapan dikunci
consecutive_failures  TINYINT UNSIGNED    -- Counter kegagalan berturut-turut
last_failed_at       TIMESTAMP NULL      -- Timestamp last failure
```

## Code Location

- **Migration**: `database/migrations/2026_02_07_131759_add_cooldown_fields_to_email_otps_table.php`
- **Controller**: `app/Http/Controllers/Auth/RegisterOtpController.php`
- **Test**: `test_otp_cooldown.ps1`
- **Docs**: `SECURITY_PATCH_OTP_COOLDOWN.md`

## Error Messages

| Condition | HTTP Code | Message |
|-----------|-----------|---------|
| Wrong OTP (1st failure) | 422 | Kode OTP salah. Sisa percobaan: 4 |
| Wrong OTP (2nd failure) | 422 | Kode OTP salah. Sisa percobaan: 3. Akun dikunci selama 1 menit. |
| During cooldown | 429 | Terlalu banyak percobaan gagal. Silakan coba lagi dalam X menit. |
| Max attempts (5) | 422 | Terlalu banyak percobaan gagal. Silakan minta OTP baru. |

## Before Production

⚠️ **PENTING - Hapus file testing:**
- [ ] Delete `app/Http/Controllers/Api/RateLimitTestController.php`
- [ ] Remove test routes dari `routes/api.php`
- [ ] Delete `test_otp_cooldown.ps1`

## Security Score

| Metric | Before | After |
|--------|--------|-------|
| OTP Security | 3/10 | 8/10 |
| Brute Force Protection | WEAK | STRONG |
| Time to brute force | Seconds | Minutes/Hours |
| Protection Layers | 1 | 3 |
