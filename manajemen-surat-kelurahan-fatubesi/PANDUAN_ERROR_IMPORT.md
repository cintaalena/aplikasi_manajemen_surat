# 🚨 PANDUAN ERROR HANDLING - IMPORT DATABASE PENDUDUK

## ✅ FITUR NOTIFIKASI ERROR YANG SUDAH DITAMBAHKAN

Sistem sekarang memberikan **notifikasi error yang detail** untuk setiap masalah yang terjadi saat import file, sehingga user bisa tahu persis apa yang salah dan bagaimana memperbaikinya.

---

## 📋 JENIS-JENIS ERROR & SOLUSINYA

### **1. ERROR UPLOAD FILE**

#### **❌ "File harus dipilih!"**
**Penyebab:** User klik tombol import tanpa pilih file terlebih dahulu  
**Solusi:** Klik tombol "Import Excel/CSV" dan pilih file dari komputer

#### **❌ "Format file harus .csv, .txt, .xlsx, atau .xls"**
**Penyebab:** File yang dipilih bukan format yang didukung (misal: .doc, .pdf, .jpg)  
**Solusi:** 
- Convert file ke format Excel (.xlsx atau .xls) atau CSV (.csv)
- Pastikan file asli adalah file Excel/CSV

#### **❌ "Ukuran file maksimal 10MB"**
**Penyebab:** File terlalu besar (lebih dari 10MB)  
**Solusi:**
- Split file Excel menjadi beberapa file kecil
- Delete kolom/baris yang tidak perlu
- Compress file dengan menghapus formatting berlebihan

---

### **2. ERROR MEMBACA FILE**

#### **❌ "Gagal membaca file Excel (.xlsx): [error detail]"**
**Penyebab:** 
- File Excel corrupt/rusak
- File sedang dibuka di Microsoft Excel
- Format file tidak valid

**Solusi:**
1. Tutup file di Microsoft Excel/LibreOffice
2. Coba "Save As" file ke format baru
3. Buka file di Excel, lalu Save As → pilih "Excel Workbook (.xlsx)"
4. Jika masih error, convert ke CSV dulu

#### **❌ "Gagal membaca file Excel (.xls): [error detail]"**
**Penyebab:** File format Excel 97-2003 (.xls) tidak valid  
**Solusi:**
1. Buka file di Microsoft Excel
2. Save As → pilih format "Excel Workbook (.xlsx)" (format yang lebih baru)
3. Upload file .xlsx yang baru

#### **❌ "Gagal membuka file CSV"**
**Penyebab:** File CSV sedang dibuka di aplikasi lain  
**Solusi:**
1. Tutup file CSV di Excel/Notepad/aplikasi lain
2. Upload ulang file

---

### **3. ERROR FILE KOSONG**

#### **❌ "File kosong atau tidak memiliki data"**
**Penyebab:** File Excel/CSV tidak memiliki data sama sekali  
**Solusi:**
- Pastikan file memiliki minimal 1 baris header dan 1 baris data
- Cek apakah ada sheet lain yang berisi data (Excel punya banyak sheet)

#### **❌ "File hanya memiliki header tanpa data"**
**Penyebab:** File hanya punya 1 baris (header) tanpa data  
**Contoh:**
```
| RT | RW | Nama | ... |
|----|----|----- |-----|
(Tidak ada baris data)
```
**Solusi:** Tambahkan minimal 1 baris data setelah header

---

### **4. ERROR HEADER/KOLOM**

#### **❌ "Tidak ada kolom yang dikenali dari header file"**
**Penyebab:** Nama kolom di file tidak sesuai dengan yang diharapkan sistem  
**Yang ditemukan:** Header ditemukan: ABC, XYZ, 123...

**Solusi:**
Pastikan file memiliki minimal kolom berikut:
- `Nama` atau `Nama Anggota` atau `Nama Anggota Keluarga`
- `Kode Keluarga` atau `No KK` atau `Nomor KK`
- `RT`
- `RW`
- `Dusun`

**Contoh header yang BENAR:**
```
| RT | RW | Dusun | Alamat | Kode Keluarga | Nama Kepala Keluarga | No. | NIK | Nama Anggota Keluarga | ... |
```

#### **❌ "Kolom wajib tidak ditemukan: nama, kode_keluarga"**
**Penyebab:** File tidak memiliki kolom `Nama` atau `Kode Keluarga`  
**Header file Anda:** [list header yang ada]

**Solusi:**
1. Tambahkan kolom `Nama` atau `Nama Anggota Keluarga`
2. Tambahkan kolom `Kode Keluarga` atau `No KK`
3. Pastikan nama kolom persis sesuai (case-insensitive OK)

---

### **5. ERROR DATA TIDAK VALID**

#### **❌ "Baris X: Jumlah kolom tidak sesuai"**
**Penyebab:** Baris data tidak memiliki jumlah kolom yang sama dengan header  
**Contoh:**
```
Header: 21 kolom
Baris 5: 18 kolom (kurang 3 kolom)
```

**Solusi:**
1. Cek baris ke-X di Excel
2. Pastikan semua kolom terisi (bisa kosong, tapi kolom harus ada)
3. Jangan ada cell yang merged/digabung

#### **❌ "Baris X: Kode Keluarga kosong"**
**Penyebab:** Kolom `Kode Keluarga` / `No KK` kosong di baris tertentu  
**Solusi:** Isi kolom Kode Keluarga dengan nomor KK yang valid

#### **❌ "Baris X: Nama kosong"**
**Penyebab:** Kolom `Nama` kosong di baris tertentu  
**Solusi:** Isi kolom Nama dengan nama anggota keluarga

---

### **6. ERROR DATABASE**

#### **❌ "Baris X: Error database - Duplicate entry for key 'nik'"**
**Penyebab:** NIK yang diinput sudah ada di database  
**Solusi:**
- Cek apakah NIK duplikat di file Excel
- Jika NIK memang sama, hapus salah satu atau kosongkan kolom NIK

#### **❌ "Import GAGAL pada baris X: [error detail]"**
**Penyebab:** Error tidak terduga saat menyimpan data  
**Yang sudah berhasil:** INSERT X, UPDATE Y

**Solusi:**
1. Cek format data pada baris X:
   - Tanggal lahir: dd-mm-yyyy atau dd/mm/yyyy
   - Jenis kelamin: L atau P (bukan Laki-laki/Perempuan, sistem akan auto-convert)
   - NIK: maksimal 20 karakter, angka saja
2. Pastikan tidak ada karakter special yang aneh
3. Data yang sudah berhasil diimport tidak perlu diimport ulang

---

## ✅ NOTIFIKASI SUKSES

### **"✅ Import selesai!"**
Menampilkan statistik lengkap:
```
📊 Statistik:
• Berhasil INSERT: X data baru
• Berhasil UPDATE: Y data existing  
• Dilewati: Z baris
• Total diproses: (X+Y+Z) baris

⚠️ Peringatan (5 error pertama):
Baris 10: Nama kosong
Baris 15: NIK duplikat
... dan 3 baris lainnya dilewati
```

**Arti:**
- **INSERT:** Data baru yang berhasil ditambahkan
- **UPDATE:** Data yang sudah ada dan di-update (berdasarkan NIK atau KK+Nama)
- **Dilewati:** Baris yang tidak valid/error dan dilewati

---

## 🎯 TIPS MENGHINDARI ERROR

### **1. Persiapan File Excel**
✅ Pastikan file dalam format .xlsx atau .xls  
✅ Tutup file sebelum upload  
✅ Hapus baris kosong di tengah data  
✅ Jangan merge cells  
✅ Header di baris pertama, data mulai baris kedua

### **2. Header Wajib**
Minimal kolom yang HARUS ada (case-insensitive):
- ✅ `Nama` / `Nama Anggota` / `Nama Anggota Keluarga`
- ✅ `Kode Keluarga` / `No KK` / `Nomor KK`

Kolom opsional (recommended):
- RT, RW, Dusun, Alamat
- Nama Kepala Keluarga
- NIK, Jenis Kelamin, Hubungan
- Tempat Lahir, Tanggal Lahir, Usia
- Status, Agama, Golongan Darah
- Kewarganegaraan, Etnis/Suku
- Pendidikan, Pekerjaan

### **3. Format Data**
✅ **Jenis Kelamin:** L, P, Laki-laki, Perempuan, LAKI-LAKI (auto-normalize ke L/P)  
✅ **Tanggal Lahir:** dd-mm-yyyy, dd/mm/yyyy, yyyy-mm-dd (auto-parse)  
✅ **RT/RW:** 1, 01, 001 (auto-format ke 001)  
✅ **NIK:** 16 digit angka, unique (jika diisi)

### **4. Validasi Sebelum Upload**
Cek di Excel:
1. ✅ Semua baris memiliki Nama dan Kode Keluarga
2. ✅ NIK tidak duplikat (jika diisi)
3. ✅ Jenis Kelamin: L atau P
4. ✅ Tanggal format benar
5. ✅ Tidak ada baris kosong di tengah
6. ✅ Jumlah kolom sama di semua baris

---

## 🔍 CARA MEMBACA NOTIFIKASI ERROR

### **Contoh Error:**
```
❌ Import GAGAL pada baris 25

Error: Column 'nama' cannot be null

Solusi:
1. Cek format data pada baris 25
2. Pastikan NIK tidak duplikat (jika ada)
3. Pastikan format tanggal benar (dd-mm-yyyy)
4. Pastikan jenis kelamin L atau P

Data yang sukses di-import: INSERT 20, UPDATE 4
```

**Cara membaca:**
- **Baris 25:** Buka Excel, cek baris ke-25 (row 25 di Excel)
- **Column 'nama' cannot be null:** Kolom Nama kosong
- **INSERT 20, UPDATE 4:** 24 data berhasil, hanya baris 25 yang error
- **Solusi:** Lengkapi data yang kurang, upload ulang file (24 data yang sudah sukses akan di-update, bukan duplikat)

---

## 📞 TROUBLESHOOTING LANJUTAN

### **Masalah: Error terus meskipun sudah diperbaiki**
**Solusi:**
1. Clear cache browser: Ctrl+Shift+Del → Clear semua
2. Hard refresh: Ctrl+Shift+R atau Ctrl+F5
3. Buka Incognito mode
4. Coba browser lain

### **Masalah: Data tidak tampil meskipun import sukses**
**Solusi:**
1. Refresh halaman (F5 atau Ctrl+R)
2. Cek filter/search (reset filter)
3. Cek pagination (mungkin data di halaman lain)
4. Logout dan login ulang

### **Masalah: File terlalu besar (>10MB)**
**Solusi:**
1. Split file menjadi beberapa file kecil (misal: per dusun)
2. Upload satu per satu
3. Update akan auto-merge jika NIK/KK sama

---

## 📝 CONTOH FILE YANG BENAR

```csv
RT,RW,Dusun,Alamat,Kode Keluarga,Nama Kepala Keluarga,No.,NIK,Nama Anggota Keluarga,Jenis Kelamin,Hubungan,Tempat Lahir,Tanggal Lahir,Usia,Status,Agama,GDarah,Kewarganegaraan,Etnis/Suku,Pendidikan,Pekerjaan
001,001,Fatubesi,Jl. Merdeka No.1,5371061404160020,ABD. RASYID,1,7307051408690001,ABD. RASYID,L,Kepala Keluarga,Kupang,14-08-1989,35,Kawin,Islam,A,WNI,Timor,S1,PNS
001,001,Fatubesi,Jl. Merdeka No.1,5371061404160020,ABD. RASYID,2,7307057112740136,MULIATI,P,Istri,Kupang,31-12-1974,50,Kawin,Islam,B,WNI,Timor,SMA,Pedagang
```

---

**Update:** 9 Februari 2026  
**Sistem:** Manajemen Surat Kelurahan Fatubesi  
**Fitur:** Error Handling & Validation Import Excel/CSV
