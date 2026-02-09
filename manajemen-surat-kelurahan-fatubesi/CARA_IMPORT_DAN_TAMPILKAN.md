# CARA IMPORT DATABASE PENDUDUK & MENAMPILKAN DI WEB

## ✅ YANG SUDAH DIKERJAKAN

1. ✅ **Model Penduduk** - Updated dengan 21 field data individual
2. ✅ **Controller** - Support import Excel (.xls, .xlsx) dan CSV dengan auto-mapping header  
3. ✅ **Migration** - Struktur database penduduk sudah siap (21 field)
4. ✅ **Frontend Vue** - Tampilan tabel 21 kolom sudah diupdate
5. ✅ **Library** - SimpleXLS & SimpleXLSX sudah terinstall

---

## 📋 LANGKAH-LANGKAH IMPORT & TAMPILKAN DATA

### **LANGKAH 1: Start MySQL/XAMPP**

Anda perlu menjalankan database MySQL terlebih dahulu:

**Jika menggunakan XAMPP:**
1. Buka XAMPP Control Panel
2. Klik tombol **"Start"** pada MySQL
3. Tunggu hingga status berubah menjadi hijau (Running)

**Jika menggunakan MySQL Service:**
```powershell
net start MySQL
# atau
net start MySQL80
# atau cek service name dengan:
Get-Service | Where-Object {$_.DisplayName -like "*mysql*"}
```

### **LANGKAH 2: Jalankan Migration Database**

```powershell
cd "c:\Users\Lenovo\Documents\4 RPLK\TA\TA_FILE\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi"

# Jalankan migration (buat tabel baru dengan struktur 21 field)
php artisan migrate --force

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **LANGKAH 3: Start Laravel Server**

```powershell
# Di terminal yang sama atau terminal baru
php artisan serve
```

Server akan berjalan di: `http://127.0.0.1:8000`

### **LANGKAH 4: Import Data via Web Interface**

#### **Cara 1: Import via Browser (RECOMMENDED)** ✅

1. Buka browser (Chrome/Edge/Firefox)
2. Akses: `http://127.0.0.1:8000`
3. Login dengan akun Anda
4. Klik menu **"Database Penduduk"**
5. Klik tombol **"Import Excel/CSV"**
6. Pilih file **`database_penduduk.xls`** dari folder project
7. Tunggu proses import selesai
8. Data akan otomatis tampil di tabel dengan 21 kolom!

#### **Cara 2: Import via PowerShell Script** (Alternative)

```powershell
# Jalankan script import otomatis
.\import_penduduk.ps1
```

Script ini akan:
- ✅ Cek koneksi database
- ✅ Jalankan migration jika belum
- ✅ Konversi Excel ke format yang bisa dibaca
- ✅ Import semua data ke database
- ✅ Tampilkan hasil (insert/update/skip)

---

## 📊 STRUKTUR DATA YANG DIIMPORT

File **`database_penduduk.xls`** memiliki 21 kolom:

| No | Kolom | Database Field | Keterangan |
|----|-------|----------------|------------|
| 1 | RT | rt | Auto-format ke 3 digit (001) |
| 2 | RW | rw | Auto-format ke 3 digit (001) |
| 3 | Dusun | dusun | Nama dusun |
| 4 | Alamat | alamat | Alamat lengkap |
| 5 | Kode Keluarga | kode_keluarga | Nomor KK |
| 6 | Nama Kepala Keluarga | nama_kepala_keluarga | Nama KK |
| 7 | No. | no_urut | Nomor urut anggota |
| 8 | NIK | nik | NIK (unique) |
| 9 | Nama Anggota Keluarga | nama | Nama individu |
| 10 | Jenis Kelamin | jenis_kelamin | L/P (auto-normalize) |
| 11 | Hubungan | hubungan | Hubungan dengan KK |
| 12 | Tempat Lahir | tempat_lahir | Tempat lahir |
| 13 | Tanggal Lahir | tanggal_lahir | Auto-parse berbagai format |
| 14 | Usia | usia | Usia (tahun) |
| 15 | Status | status_perkawinan | Status perkawinan |
| 16 | Agama | agama | Agama |
| 17 | GDarah | golongan_darah | A/B/AB/O |
| 18 | Kewarganegaraan | kewarganegaraan | Default: WNI |
| 19 | Etnis / Suku | etnis | Etnis/suku |
| 20 | Pendidikan | pendidikan | Pendidikan terakhir |
| 21 | Pekerjaan | pekerjaan | Pekerjaan |

---

## 🎯 FITUR AUTO-MAPPING HEADER

Controller sudah dilengkapi dengan **auto-mapping** yang cerdas. Header Excel Anda tidak perlu persis sama, sistem akan otomatis mengenali variasi nama kolom:

**Contoh:**
- "Kode Keluarga" / "No KK" / "Nomor KK" → `kode_keluarga`
- "JK" / "Jenis Kelamin" / "Kelamin" → `jenis_kelamin`
- "Tgl Lahir" / "Tanggal Lahir" → `tanggal_lahir`
- "Gol Darah" / "GDarah" / "Golongan Darah" → `golongan_darah`
- Dan seterusnya...

**Auto-Normalisasi:**
- Jenis Kelamin: "Laki-laki"/"LAKI-LAKI"/"L"/"Male" → `L`
- RT/RW: "1"/"01" → `001` (3 digit)
- Tanggal: Support format d-m-Y, d/m/Y, Y-m-d, dll

---

## 🔍 CARA MELIHAT DATA DI WEB

Setelah import berhasil:

1. **Tabel 21 Kolom** - Data ditampilkan dalam tabel scrollable dengan 21 kolom
2. **Filter & Search** - Cari berdasarkan NIK, Nama, No KK, atau Alamat
3. **Filter Dusun/RT/RW** - Filter spesifik per wilayah
4. **Pagination** - Pilih 10/20/30/50 data per halaman
5. **Export CSV** - Download data filtered ke CSV

**Tampilan Kolom di Web:**
```
RT | RW | Dusun | Alamat | No KK | Kepala Keluarga | 
No. | NIK | Nama | JK | Hubungan | Tempat Lahir | 
Tanggal Lahir | Usia | Status | Agama | Gol. Darah | 
Kewarganegaraan | Etnis/Suku | Pendidikan | Pekerjaan
```

---

## 🚨 TROUBLESHOOTING

### **Error: SQLSTATE[HY000] [2002]**
**Solusi:** MySQL belum running. Start XAMPP/MySQL terlebih dahulu.

### **Error: Unknown archive format**
**Solusi:** Library SimpleXLS sudah terinstall. Pastikan controller sudah diupdate.

### **Import gagal / Data tidak masuk**
**Solusi:** 
1. Cek apakah header Excel sesuai (21 kolom)
2. Minimal kolom `Nama` dan `Kode Keluarga` harus terisi
3. Lihat flash message error untuk detail

### **Data tidak tampil di web**
**Solusi:**
1. Refresh halaman (Ctrl+F5)
2. Cek apakah import berhasil (lihat flash message)
3. Gunakan filter/search untuk mencari data

---

## 📁 FILE-FILE PENTING

- **Controller:** `app/Http/Controllers/PendudukController.php`
- **Model:** `app/Models/Penduduk.php`
- **Migration:** `database/migrations/2026_02_08_080000_recreate_penduduks_table_for_individual_data.php`
- **Frontend:** `resources/js/Pages/Penduduk/Index.vue`
- **Data Excel:** `database_penduduk.xls`
- **Script Import:** `import_penduduk.ps1`

---

## ✨ FITUR YANG SUDAH READY

✅ Import Excel (.xls, .xlsx) dan CSV  
✅ Auto-mapping header fleksibel  
✅ Auto-normalisasi data (JK, RT/RW, Tanggal)  
✅ Import validation (NIK unique, data minimal)  
✅ Update jika data sudah ada (by NIK atau KK+Nama)  
✅ Tampilan 21 kolom data individual  
✅ Filter & Search multi-field  
✅ Export CSV dengan semua field  
✅ Pagination & user-friendly interface  

---

## 🎉 SELESAI!

Setelah mengikuti langkah di atas, sistem sudah siap digunakan:
- ✅ Database terstruktur 21 field
- ✅ Import otomatis dari Excel
- ✅ Tampilan web 21 kolom lengkap
- ✅ Filter, search, export ready

**Akses:** `http://127.0.0.1:8000/penduduk`

---

*Dibuat: 9 Februari 2026*  
*Sistem: Manajemen Surat Kelurahan Fatubesi*
