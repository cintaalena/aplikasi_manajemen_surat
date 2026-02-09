# PANDUAN FITUR IMPORT EXCEL - DATABASE PENDUDUK

## ✅ FITUR YANG SUDAH DIBUAT

### 1. **Support Import File Excel & CSV**
   - Format: `.xlsx`, `.xls`, `.csv`, `.txt`
   - Maximum file size: 10MB
   - Library: SimpleXLSX (sudah terinstall)

### 2. **Struktur Database Baru - Data Individu Lengkap**
   
Tabel `penduduks` sekarang menyimpan data per individu dengan field:

**Data Keluarga:**
- `kode_keluarga` - Nomor KK
- `nama_kepala_keluarga` - Nama Kepala Keluarga
- `alamat` - Alamat lengkap
- `rt`, `rw`, `dusun` - Lokasi

**Data Individu:**
- `no_urut` - Nomor urut dalam keluarga
- `nik` - NIK (unik)
- `nama` - Nama anggota keluarga
- `jenis_kelamin` - L/P
- `hubungan` - Hubungan dengan KK
- `tempat_lahir` - Tempat lahir
- `tanggal_lahir` - Tanggal lahir
- `usia` - Usia
- `status_perkawinan` - Status perkawinan
- `agama` - Agama
- `golongan_darah` - A/B/AB/O
- `kewarganegaraan` - Default: WNI
- `etnis` - Etnis/Suku
- `pendidikan` - Pendidikan terakhir
- `pekerjaan` - Pekerjaan

### 3. **Import Otomatis dengan Header Mapping**

Controller sudah dilengkapi dengan auto-mapping header yang fleksibel:

| Variasi Header Excel/CSV | Akan dimapping ke |
|-------------------------|-------------------|
| No, No., Nomor | no_urut |
| Kode Keluarga, No KK, Nomor KK | kode_keluarga |
| Kepala Keluarga, Nama KK | nama_kepala_keluarga |
| Nama, Nama Anggota, Nama Anggota Keluarga | nama |
| JK, Jenis Kelamin, Kelamin | jenis_kelamin |
| Tempat Lahir | tempat_lahir |
| Tanggal Lahir, Tgl Lahir | tanggal_lahir |
| Status, Status Perkawinan, Status Kawin | status_perkawinan |
| GDarah, Gol Darah, Golongan Darah | golongan_darah |
| Etnis, Suku, Etnis/Suku | etnis |
| Warga Negara, Kewarganegaraan | kewarganegaraan |

### 4. **Tampilan Tabel Lengkap**

Table sekarang menampilkan 21 kolom:
- RT, RW, Dusun, Alamat
- No KK, Kepala Keluarga
- No., NIK, Nama, JK
- Hubungan, Tempat Lahir, Tanggal Lahir, Usia
- Status, Agama, Gol. Darah
- Kewarganegaraan, Etnis/Suku
- Pendidikan, Pekerjaan

### 5. **Export CSV dengan Field Lengkap**

Export akan menghasilkan CSV dengan semua  field di atas.

---

## 📋 CARA MENGGUNAKAN

### **LANGKAH 1: Update Database**

Jalankan perintah berikut di terminal:

```powershell
# Pastikan Anda di directory project
cd "c:\Users\Lenovo\Documents\4 RPLK\TA\TA_FILE\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi"

# Buat file bootstrap cache jika belum ada
if (!(Test-Path "bootstrap\cache\packages.php")) {
    "<?php return [];" | Out-File "bootstrap\cache\packages.php" -Encoding UTF8
}
if (!(Test-Path "bootstrap\cache\services.php")) {
    "<?php return [];" | Out-File "bootstrap\cache\services.php" -Encoding UTF8
}

# Rollback migration lama (opsional, jika ada error)
php artisan migrate:rollback --step=2

# Jalankan migration baru
php artisan migrate

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### **LANGKAH 2: Buka Aplikasi**

1. Buka browser dalam **Incognito mode** (`Ctrl + Shift + N`)
2. Akses: `http://127.0.0.1:8000`
3. Login dengan akun Anda
4. Klik menu **"Database Penduduk"**

### **LANGKAH 3: Import File Excel**

1. Siapkan file Excel (.xlsx atau .xls) dengan header sesuai format
2. Klik tombol **"Import CSV"** (sekarang support Excel juga!)
3. Pilih file Excel Anda
4. Sistem akan otomatis:
   - Membaca file Excel
   - Melakukan mapping header
   - Validasi data
   - Insert/Update ke database
5. Akan muncul notifikasi hasil import

---

## 📊 CONTOH FORMAT FILE EXCEL

### **Contoh 1 - Format Standar**

| RT | RW | Dusun | Alamat | Kode Keluarga | Nama Kepala Keluarga | No. | NIK | Nama Anggota Keluarga | Jenis Kelamin | Hubungan | Tempat Lahir | Tanggal Lahir | Usia | Status | Agama | GDarah | Kewarganegaraan | Etnis/Suku | Pendidikan | Pekerjaan |
|----|----|-|--------|---------------|----------------------|-----|-----|----------------------|---------------|----------|--------------|----------------|------|--------|-------|--------|-----------------|------------|------------|-----------|
| 001 | 001 | Fatubesi | Jl. Merdeka No.1 | 3301010101000001 | Budi Santoso | 1 | 3301011980010001 | Budi Santoso | L | Kepala Keluarga | Kupang | 01-01-1980 | 44 | Kawin | Islam | A | WNI | Timor | S1 | PNS |
| 001 | 001 | Fatubesi | Jl. Merdeka No.1 | 3301010101000001 | Budi Santoso | 2 | 3301011985020001 | Siti Aminah | P | Istri | Kupang | 15-02-1985 | 39 | Kawin | Islam | B | WNI | Timor | SMA | Ibu Rumah Tangga |

### **Contoh 2 - Format Alternatif (Tetap akan dibaca)**

| rt | rw | nama dusun | alamat | no kk | kepala keluarga | no | nik | nama | jk | hubungan | tempat lahir | tgl lahir | usia | status kawin | agama | gol darah | warga negara | suku | pendidikan | pekerjaan |
|----|----|------------|--------|-------|-----------------|----|-|------|-------|-----------|--------------|-----------|------|--------------|-------|-----------|--------------|------|------------|-----------|

**Catatan:** 
- Header bisa menggunakan huruf besar/kecil, dengan/tanpa spasi
- Sistem akan otomatis mengenali berbagai variasi nama kolom
- Kolom yang tidak wajib bisa dikosongkan

---

## 🔍 VALIDASI & ERROR HANDLING

### **Auto Validation:**
- NIK harus unik (jika diisi)
- Kode Keluarga + Nama harus ada
- Jenis Kelamin otomatis dinormalisasi: L/Laki-laki/Male → L
- Tanggal lahir support berbagai format (d-m-Y, d/m/Y, Y-m-d, dll)
- Data duplikat akan di-update, bukan insert baru

### **Error Handling:**
- File invalid/corrupt → Error message
- Header tidak sesuai → Auto-mapping
- Data tidak valid → Di-skip, lanjut ke baris berikutnya
- Hasil import menampilkan: Berhasil, Diperbarui, Dilewati

---

## 🎯 TIPS & TRIK

1. **Test dengan Data Kecil Dulu**
   - Import 5-10 baris dulu untuk test format
   - Cek hasilnya di tabel
   - Baru import data lengkap

2. **Excel vs CSV**
   - Excel (.xlsx) lebih mudah untuk edit dengan warna/format
   - CSV lebih ringan dan cepat untuk data besar
   - Keduanya fully supported!

3. **Update Data**
   - Jika NIK sudah ada → Data akan di-update
   - Jika Kode Keluarga + No Urut sama → Data akan di-update
   - Jika baru → Data akan di-insert

4. **Export untuk Template**
   - Klik "Export CSV" untuk dapat template kosong
   - Edit di Excel
   - Re-import

---

## ⚠️ TROUBLESHOOTING

### **Masalah: "Library SimpleXLSX tidak terinstall"**
```powershell
composer require shuchkin/simplexlsx
```

### **Masalah: "Migration error - bootstrap/cache not writable"**
```powershell
# Buat file cache manual
"<?php return [];" | Out-File "bootstrap\cache\packages.php" -Encoding UTF8
"<?php return [];" | Out-File "bootstrap\cache\services.php" -Encoding UTF8

# Atau hapus dan buat ulang directory
Remove-Item "bootstrap\cache" -Recurse -Force
New-Item -ItemType Directory -Path "bootstrap\cache"
```

### **Masalah: "Column not found error"**
```powershell
# Reset database
php artisan migrate:fresh
```

### **Masalah: "Import button tidak muncul"**
```powershell
# Rebuild assets
npm run build

# Buka di Incognito window
# Ctrl + Shift + N
```

---

## 📞 SUPPORT

Jika ada masalah, cek:
1. Laravel log: `storage/logs/laravel.log`
2. Browser console (F12)
3. Database error di terminal

---

**Selamat Menggunakan Fitur Import Excel! 🎉**
