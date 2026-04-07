# 🧪 Testing Rate Limiting - Panduan

## Masalah: Semua Request Langsung Di-Block

Jika saat testing semua request langsung dapat 429 (di-block), ada beberapa penyebab:

### 🔍 Penyebab Umum:

1. **Cache belum di-clear** - Cache lama masih tersisa
2. **Delay terlalu cepat** - Request dikirim terlalu cepat
3. **Session/Cookie** - Browser menyimpan state lama
4. **Database cache issue** - Tabel cache bermasalah

---

## ✅ Solusi Step-by-Step

### Step 1: Clear Cache
```powershell
# Jalankan script ini
.\scripts\clear_rate_limit.ps1

# Atau manual:
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Step 2: Restart Server
```powershell
# Stop server (Ctrl+C)
# Lalu start ulang
php artisan serve
```

### Step 3: Test Simple
```powershell
# Test sederhana dengan interval lebih lama
.\scripts\simple_test.ps1
```

### Step 4: Full Test
```powershell
# Jika simple test berhasil, jalankan full test
.\scripts\test_rate_limiting.ps1
```

---

## 📊 Test Scripts Available

| Script | Keterangan | Kapan Digunakan |
|--------|-----------|-----------------|
| `simple_test.ps1` | Test sederhana, 1 endpoint | Debugging, verifikasi cepat |
| `test_rate_limiting.ps1` | Test lengkap semua endpoint | Testing komprehensif |
| `clear_rate_limit.ps1` | Clear cache | Sebelum testing |

---

## 🎯 Hasil yang Diharapkan

### Simple Test (OTP Request)
```
Request 1 → ✅ SUCCESS (Sisa: 2)
Request 2 → ✅ SUCCESS (Sisa: 1)
Request 3 → ✅ SUCCESS (Sisa: 0)
Request 4 → 🚫 BLOCKED (429)
Request 5 → 🚫 BLOCKED (429)
```

### Full Test

**Test 1: OTP Request**
- Request 1-3: SUCCESS ✅
- Request 4: BLOCKED (429) 🚫

**Test 2: OTP Verify**
- Attempt 1-5: REJECTED (Wrong OTP) ⚠️
- Attempt 6-7: BLOCKED 🚫

**Test 3: Login**
- Attempt 1-5: REJECTED (Invalid credentials) ⚠️
- Attempt 6: BLOCKED (429) 🚫

---

## 🔧 Troubleshooting

### Problem 1: Semua SUCCESS, tidak ada yang BLOCKED

**Penyebab**: Rate limiting tidak aktif

**Solusi**:
```powershell
# Check apakah middleware terdaftar
php artisan route:list | Select-String "throttle"

# Harus muncul:
# POST register/request-otp ... throttle:3,10
# POST register/verify-otp  ... throttle:5,5
# POST login                ... throttle:5,1
```

### Problem 2: Semua BLOCKED dari request pertama

**Penyebab**: Cache kotor atau throttle key bentrok

**Solusi**:
```powershell
# 1. Clear semua cache
.\scripts\clear_rate_limit.ps1

# 2. Check database cache
php artisan tinker
>>> DB::table('cache')->where('key', 'like', '%throttle%')->count()
>>> DB::table('cache')->where('key', 'like', '%throttle%')->delete()

# 3. Restart server
# Stop (Ctrl+C) dan start ulang
php artisan serve
```

### Problem 3: Error 419 (CSRF) pada Login

**Penyebab**: Login butuh CSRF token dari frontend

**Solusi**: Test login via browser atau skip test login

---

## 📝 Manual Testing (Alternative)

### Via Browser/Postman

1. **Test OTP Request**:
   ```
   POST http://localhost/register/request-otp
   Body: {
     "name": "Test",
     "email": "test@example.com",
     "jabatan": "lurah",
     "password": "password123",
     "password_confirmation": "password123"
   }
   
   Kirim 4x → ke-4 harus 429
   ```

2. **Test OTP Verify**:
   ```
   POST http://localhost/register/verify-otp
   Body: {
     "email": "test@example.com",
     "otp": "111111"
   }
   
   Kirim 7x → ke-6 dan 7 harus blocked
   ```

---

## 🐛 Debug Mode

Tambahkan debug output di middleware:

```php
// app/Http/Middleware/ThrottleWithLog.php
Log::info('Throttle check', [
    'key' => $key,
    'max' => $maxAttempts,
    'current' => $this->limiter->attempts($key),
    'remaining' => $maxAttempts - $this->limiter->attempts($key),
]);
```

Lalu monitor log:
```powershell
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

---

## ✅ Checklist Testing

Sebelum testing, pastikan:

- [ ] Server Laravel running (`php artisan serve`)
- [ ] Database migrate sudah dijalankan
- [ ] Cache sudah di-clear
- [ ] Tidak ada testing sebelumnya yang masih cached
- [ ] Delay antar request cukup (min 1-2 detik)

Saat testing:

- [ ] Monitor response headers (X-RateLimit-*)
- [ ] Monitor logs (storage/logs/laravel.log)
- [ ] Catat timestamp setiap request
- [ ] Screenshot hasil untuk dokumentasi TA

Setelah testing:

- [ ] Clear cache lagi untuk testing berikutnya
- [ ] Verifikasi data di database (email_otps, cache)
- [ ] Dokumentasikan hasil

---

## 📸 Screenshot untuk TA

Yang perlu di-screenshot:

1. Output test script (showing SUCCESS → BLOCKED pattern)
2. Browser DevTools Network tab (showing 429 response)
3. Response headers (X-RateLimit-Limit, X-RateLimit-Remaining)
4. Laravel log (showing "Rate limit exceeded")
5. Database cache table (showing throttle entries)

---

## 💡 Tips

- **Gunakan email berbeda** setiap test untuk avoid conflict
- **Clear cache** sebelum setiap test session
- **Monitor logs** real-time saat testing
- **Catat timestamp** untuk prove delay works
- **Test di browser** jika script PowerShell error

---

## 🆘 Bantuan

Jika masih bermasalah:

1. Baca error message dengan teliti
2. Check logs: `storage/logs/laravel.log`
3. Verify routes: `php artisan route:list`
4. Check cache: `php artisan cache:clear`
5. Restart server

Atau hubungi developer/mentor! 😊
