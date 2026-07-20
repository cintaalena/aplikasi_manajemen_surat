<?php

/**
 * Functional Testing — Aplikasi Manajemen Surat Kelurahan Fatubesi
 * Mencakup TC01 s.d. TC10 (40 skenario) sesuai dokumen test case skripsi.
 *
 * Jalankan: php artisan test --filter FunctionalTest --testdox
 */

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\LetterDisposition;
use App\Models\LetterDocument;
use App\Models\LetterNotification;
use App\Models\Penduduk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FunctionalTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────
    // HELPER: buat user sesuai peran
    // ─────────────────────────────────────────

    private function buatAdmin(): User
    {
        return User::create([
            'name'                 => 'SUPERADMIN',
            'email'                => 'admin@test.local',
            'password'             => Hash::make('Admin123!'),
            'credential_code_hash' => null,
            'jabatan'              => 'admin',
            'role'                 => 'admin',
            'is_active'            => true,
            'email_verified_at'    => now(),
        ]);
    }

    private function buatStaf(string $kode = 'B-001', string $name = 'STAF TEST'): User
    {
        static $counter = 0;
        $counter++;
        return User::create([
            'name'                 => $name,
            'email'                => 'staf' . $counter . '@test.local',
            'password'             => Hash::make('Password123!'),
            'credential_code_hash' => Hash::make(strtoupper($kode)),
            'jabatan'              => 'Staf Pelayanan',
            'role'                 => 'staff',
            'is_active'            => true,
            'email_verified_at'    => now(),
        ]);
    }

    private function buatLurah(): User
    {
        return User::create([
            'name'                 => 'LURAH TEST',
            'email'                => 'lurah@test.local',
            'password'             => Hash::make('Password123!'),
            'credential_code_hash' => Hash::make('A-001'),
            'jabatan'              => 'Lurah',
            'role'                 => 'lurah',
            'is_active'            => true,
            'email_verified_at'    => now(),
        ]);
    }

    private function dataPenduduk(array $override = []): array
    {
        return array_merge([
            'kode_keluarga'     => '7301012501000001',
            'nik'               => '7301012501900001',
            'nama'              => 'BUDI SANTOSO',
            'jenis_kelamin'     => 'L',
            'tempat_lahir'      => 'Kupang',
            'tanggal_lahir'     => '1990-01-25',
            'agama'             => 'Kristen',
            'pendidikan'        => 'SMA',
            'pekerjaan'         => 'Wiraswasta',
            'status_perkawinan' => 'Belum Kawin',
            'kewarganegaraan'   => 'WNI',
            'rt'                => '001',
            'rw'                => '001',
            'alamat'            => 'Jl. Test No. 1',
        ], $override);
    }

    private function buatLetterDb(array $override = []): Letter
    {
        return Letter::create(array_merge([
            'template_slug' => 'keterangan-umum',
            'title'         => 'Surat Keterangan',
            'no_surat'      => 'TEST/001/' . now()->year,
            'is_manual'     => false,
            'printed_at'    => now(),
        ], $override));
    }

    private function buatDisposisi(Letter $letter, User $lurah, User $staf, string $status = 'pending'): LetterDisposition
    {
        return LetterDisposition::create([
            'letter_id'    => $letter->id,
            'from_user_id' => $lurah->id,
            'to_user_id'   => $staf->id,
            'catatan'      => 'Harap segera ditindaklanjuti.',
            'status'       => $status,
        ]);
    }

    private function csvUploadFile(string $content, string $name = 'data.csv'): UploadedFile
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tmpPath, $content);
        return new UploadedFile($tmpPath, $name, 'text/csv', null, true);
    }

    // ═══════════════════════════════════════════════════════
    // A. LOGIN — TC01
    // ═══════════════════════════════════════════════════════

    /**
     * TC01-01 (Normal): Login dengan username, credential_code, dan kata sandi valid.
     * Diharapkan: Sistem membuat sesi login dan mengarahkan ke tampilan sesuai peran.
     */
    public function test_TC01_01_login_kredensial_valid_berhasil_masuk_dashboard(): void
    {
        $this->buatAdmin();

        $response = $this->post('/login', [
            'name'     => 'SUPERADMIN',
            'password' => 'Admin123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * TC01-02 (Alternatif): Login dengan username valid tetapi kata sandi salah.
     * Diharapkan: Sistem menolak dan menampilkan pesan login tidak valid.
     */
    public function test_TC01_02_login_password_salah_ditolak_pesan_generik(): void
    {
        $this->buatAdmin();

        $response = $this->post('/login', [
            'name'     => 'SUPERADMIN',
            'password' => 'SalahPassword!',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    /**
     * TC01-03 (Alternatif): Login staf dengan credential_code salah.
     * Diharapkan: Sistem menolak dan menampilkan pesan login tidak valid.
     */
    public function test_TC01_03_login_credential_code_salah_ditolak(): void
    {
        $this->buatStaf('B-001');

        $response = $this->post('/login', [
            'name'            => 'STAF TEST',
            'password'        => 'Password123!',
            'credential_code' => 'SALAH-999',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    /**
     * TC01-04 (Alternatif): Login dengan kolom wajib kosong.
     * Diharapkan: Sistem menampilkan pesan validasi, tidak memproses login.
     */
    public function test_TC01_04_login_kolom_kosong_menampilkan_validasi(): void
    {
        $response = $this->post('/login', [
            'name'     => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'password']);
        $this->assertGuest();
    }

    /**
     * TC01-05 (Alternatif): Akses URL internal tanpa login.
     * Diharapkan: Sistem mengarahkan kembali ke halaman login.
     */
    public function test_TC01_05_akses_url_internal_tanpa_login_diarahkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/penduduk')->assertRedirect('/login');
        $this->get('/arsip-surat')->assertRedirect('/login');
        $this->get('/disposisi-tugas')->assertRedirect('/login');
    }

    // ═══════════════════════════════════════════════════════
    // B. LOGOUT — TC02
    // ═══════════════════════════════════════════════════════

    /**
     * TC02-01 (Normal): Pengguna yang login menekan tombol logout.
     * Diharapkan: Sistem menghapus sesi dan mengarahkan ke halaman login.
     */
    public function test_TC02_01_logout_menghapus_sesi_dan_redirect_ke_home(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * TC02-02 (Alternatif): Setelah logout, coba akses kembali halaman internal.
     * Diharapkan: Sistem mengarahkan kembali ke halaman login.
     */
    public function test_TC02_02_akses_halaman_internal_setelah_logout_diarahkan_ke_login(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post('/logout');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    /**
     * TC02-03 (Alternatif): Sesi berakhir (timeout), lalu coba lakukan aksi pada sistem.
     * Diharapkan: Sistem langsung mengarahkan ke halaman login.
     */
    public function test_TC02_03_session_timeout_akses_sistem_diarahkan_ke_login(): void
    {
        $admin = $this->buatAdmin();

        // Aktifkan sesi login
        $this->actingAs($admin)->get('/dashboard')->assertOk();

        // Simulasi timeout: hapus autentikasi dan coba akses ulang
        auth()->logout();
        $this->app['auth']->forgetGuards();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/penduduk')->assertRedirect('/login');
    }

    // ═══════════════════════════════════════════════════════
    // C. DASHBOARD, MONITORING & REKAPITULASI — TC03
    // ═══════════════════════════════════════════════════════

    /**
     * TC03-01 (Normal): Login berhasil, sistem mengarahkan ke dashboard.
     * Diharapkan: Dashboard menampilkan ringkasan data penduduk dan jumlah surat.
     */
    public function test_TC03_01_dashboard_tampil_lengkap_setelah_login(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertStatus(200);
    }

    /**
     * TC03-02 (Alternatif): Akses dashboard saat sebagian data belum tersedia.
     * Diharapkan: Dashboard tetap tampil dengan nilai kosong/nol, tanpa error.
     */
    public function test_TC03_02_dashboard_tampil_normal_saat_data_kosong(): void
    {
        $admin = $this->buatAdmin();

        // Tidak ada data penduduk maupun surat yang dibuat
        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('500');
        $response->assertDontSee('Whoops');
    }

    /**
     * TC03-03 (Alternatif): Akses dashboard saat data gagal dimuat.
     * Diharapkan: Sistem menampilkan pesan error tanpa menampilkan detail teknis.
     */
    public function test_TC03_03_dashboard_metrics_tidak_expose_detail_teknis_saat_error(): void
    {
        $admin = $this->buatAdmin();

        // Simulasi kegagalan data: hapus tabel penduduks sementara
        DB::statement('DROP TABLE IF EXISTS penduduks');

        $response = $this->withExceptionHandling()
            ->actingAs($admin)
            ->get('/dashboard');

        // Tidak boleh menampilkan pesan SQL/teknis
        $response->assertDontSee('SQLSTATE');
        $response->assertDontSee('SQL');
        $response->assertDontSee('QueryException');
        // Response harus berupa HTTP (bukan blank/null)
        $this->assertNotNull($response->getContent());

        // Kembalikan tabel agar test lain tidak terganggu
        // (RefreshDatabase akan memulihkan schema di test berikutnya)
    }

    /**
     * TC03-04 (Normal): Ekspor rekapitulasi surat ke Excel.
     * Diharapkan: Sistem menghasilkan file ekspor Excel sesuai data yang ditampilkan.
     */
    public function test_TC03_04_ekspor_rekapitulasi_menghasilkan_file_excel(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get('/dashboard/export-excel');

        $response->assertOk();
        $contentDisp = $response->headers->get('content-disposition', '');
        $contentType = $response->headers->get('content-type', '');

        $this->assertTrue(
            str_contains($contentDisp, 'attachment') ||
            str_contains($contentType, 'spreadsheet') ||
            str_contains($contentType, 'excel') ||
            str_contains($contentType, 'openxmlformats')
        );
    }

    // ═══════════════════════════════════════════════════════
    // D. PENGELOLAAN PENGGUNA & PERAN — TC04
    // ═══════════════════════════════════════════════════════

    /**
     * TC04-01 (Normal): Admin menambah pengguna baru dengan data lengkap.
     * Diharapkan: Sistem memvalidasi, menyimpan data, menampilkan pesan berhasil.
     */
    public function test_TC04_01_admin_tambah_pengguna_baru_berhasil(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->post('/admin/pengguna', [
            'name'                  => 'Staf Baru',
            'jabatan'               => 'Staf Pelayanan',
            'role'                  => 'staff',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['name' => 'STAF BARU']);
    }

    /**
     * TC04-02 (Alternatif): Admin menambah pengguna dengan username yang sudah terdaftar.
     * Diharapkan: Sistem menolak dan menampilkan pesan bahwa username telah digunakan.
     * Validasi bersifat case-insensitive: "staf satu" = "STAF SATU" = duplikat.
     */
    public function test_TC04_02_admin_tambah_pengguna_username_duplikat_ditolak(): void
    {
        $admin = $this->buatAdmin();

        // Buat pengguna pertama dengan nama "STAF SATU"
        $this->actingAs($admin)->post('/admin/pengguna', [
            'name'                  => 'Staf Satu',
            'jabatan'               => 'Staf Pelayanan',
            'role'                  => 'staff',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Coba buat pengguna kedua dengan nama yang sama (huruf kecil — harus tetap ditolak)
        $response = $this->actingAs($admin)->post('/admin/pengguna', [
            'name'                  => 'staf satu',
            'jabatan'               => 'Staf Lain',
            'role'                  => 'staff',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Sistem harus menolak dengan error pada field name
        $response->assertSessionHasErrors('name');
        // Hanya boleh ada 1 user dengan nama STAF SATU
        $this->assertEquals(1, User::where('name', 'STAF SATU')->count());
    }

    /**
     * TC04-03 (Alternatif): Admin mengisi data pengguna tanpa memilih peran.
     * Diharapkan: Sistem menampilkan pesan bahwa peran wajib dipilih.
     */
    public function test_TC04_03_admin_tambah_pengguna_tanpa_peran_ditolak(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->post('/admin/pengguna', [
            'name'                  => 'Tanpa Peran',
            'jabatan'               => 'Staf',
            'role'                  => '',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('role');
    }

    /**
     * TC04-04 (Alternatif): Admin menyimpan data pengguna dengan kolom wajib belum lengkap.
     * Diharapkan: Sistem menampilkan pesan validasi pada kolom yang belum diisi.
     */
    public function test_TC04_04_admin_simpan_pengguna_kolom_wajib_kosong_ditolak(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->post('/admin/pengguna', [
            'name'     => '',
            'jabatan'  => '',
            'role'     => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'jabatan', 'role', 'password']);
    }

    /**
     * TC04-05 (Normal): Admin menonaktifkan akun pengguna yang ada.
     * Diharapkan: Sistem mengubah status nonaktif dan menampilkan pesan berhasil.
     */
    public function test_TC04_05_admin_nonaktifkan_pengguna_berhasil(): void
    {
        $admin = $this->buatAdmin();
        $staf  = $this->buatStaf();

        $response = $this->actingAs($admin)
            ->patch("/admin/pengguna/{$staf->id}/toggle-active");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($staf->fresh()->is_active);
    }

    /**
     * TC04-06 (Alternatif): Login sebagai Staf/Lurah, coba akses menu manajemen pengguna.
     * Diharapkan: Sistem menolak akses (403 Forbidden) — validasi di sisi backend.
     */
    public function test_TC04_06_staf_akses_menu_manajemen_pengguna_ditolak_403(): void
    {
        $staf = $this->buatStaf();

        $this->actingAs($staf)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($staf)->post('/admin/pengguna', [])->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════
    // E. PENGELOLAAN DATA PENDUDUK — TC05
    // ═══════════════════════════════════════════════════════

    /**
     * TC05-01 (Normal): Tambah data penduduk baru dengan NIK unik dan kolom wajib terisi.
     * Diharapkan: Sistem memvalidasi, menyimpan data, menampilkan pesan berhasil.
     */
    public function test_TC05_01_tambah_penduduk_valid_berhasil_disimpan(): void
    {
        $staf = $this->buatStaf();

        $response = $this->actingAs($staf)->post('/penduduk', array_merge(
            $this->dataPenduduk(),
            ['is_kepala_keluarga' => true]
        ));

        $response->assertRedirect();
        $this->assertDatabaseHas('penduduks', ['nik' => '7301012501900001']);
    }

    /**
     * TC05-02 (Alternatif): Tambah data penduduk dengan NIK yang sudah terdaftar.
     * Diharapkan: Sistem menolak penyimpanan dan menampilkan pesan NIK sudah digunakan.
     */
    public function test_TC05_02_tambah_penduduk_nik_duplikat_ditolak(): void
    {
        $staf = $this->buatStaf();
        Penduduk::create($this->dataPenduduk());

        $response = $this->actingAs($staf)->post('/penduduk', $this->dataPenduduk([
            'nama' => 'NAMA BERBEDA',
        ]));

        $response->assertSessionHasErrors('nik');
    }

    /**
     * TC05-03 (Alternatif): Simpan data penduduk dengan kolom wajib kosong.
     * Diharapkan: Sistem menampilkan pesan validasi pada kolom yang kosong.
     */
    public function test_TC05_03_tambah_penduduk_kolom_wajib_kosong_ditolak(): void
    {
        $staf = $this->buatStaf();

        $response = $this->actingAs($staf)->post('/penduduk', [
            'nik'           => '',
            'nama'          => '',
            'jenis_kelamin' => '',
        ]);

        $response->assertSessionHasErrors(['nik', 'nama', 'jenis_kelamin']);
    }

    /**
     * TC05-04 (Normal): Cari data penduduk dengan kata kunci yang sesuai.
     * Diharapkan: Sistem menampilkan data penduduk yang cocok dengan kata kunci.
     */
    public function test_TC05_04_cari_penduduk_kata_kunci_sesuai_tampil_hasil(): void
    {
        $staf = $this->buatStaf();
        Penduduk::create($this->dataPenduduk(['nama' => 'BUDI SANTOSO']));

        $response = $this->actingAs($staf)
            ->getJson('/penduduk/search-by-name?q=BUDI');

        $response->assertOk();
        $response->assertJsonFragment(['nama' => 'BUDI SANTOSO']);
    }

    /**
     * TC05-05 (Alternatif): Cari data penduduk dengan kata kunci yang tidak ada.
     * Diharapkan: Sistem menampilkan pesan bahwa data tidak ditemukan.
     */
    public function test_TC05_05_cari_penduduk_tidak_ditemukan_mengembalikan_kosong(): void
    {
        $staf = $this->buatStaf();

        $response = $this->actingAs($staf)
            ->getJson('/penduduk/search-by-name?q=XYZNOTFOUND999');

        $response->assertOk();
        $response->assertExactJson([]);
    }

    /**
     * TC05-06 (Normal): Pilih hapus pada salah satu data penduduk dan konfirmasi.
     * Diharapkan: Sistem meminta konfirmasi lalu menghapus data setelah dikonfirmasi.
     */
    public function test_TC05_06_hapus_penduduk_berhasil_soft_delete(): void
    {
        $staf     = $this->buatStaf();
        $penduduk = Penduduk::create($this->dataPenduduk());

        $response = $this->actingAs($staf)
            ->delete("/penduduk/{$penduduk->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('penduduks', ['id' => $penduduk->id]);
    }

    /**
     * TC05-07 (Normal): Impor file CSV berisi data penduduk yang valid.
     * Diharapkan: Sistem membaca, memvalidasi, dan menyimpan data penduduk dari file.
     */
    public function test_TC05_07_import_csv_valid_berhasil_disimpan(): void
    {
        $staf = $this->buatStaf();
        Storage::fake('local');

        $csvContent = implode("\n", [
            'kode keluarga,nama,nik,jenis kelamin,agama,rt,rw,alamat',
            '7301010001001,AGUS SUBAGYO,7301010101800001,L,Kristen,001,001,Jl Import No 1',
        ]);

        $file = $this->csvUploadFile($csvContent, 'penduduk.csv');

        $response = $this->actingAs($staf)
            ->post('/penduduk/import', ['file' => $file]);

        // importRedirect() menggunakan Inertia::location (redirect 302)
        $this->assertTrue(
            $response->isRedirection() ||
            session()->has('success'),
            'Import valid CSV harus berhasil dan memberikan respons redirect dengan sesi sukses.'
        );
    }

    /**
     * TC05-08 (Alternatif): Impor file dengan format/tipe tidak valid (bukan CSV/Excel).
     * Diharapkan: Sistem menolak file dan menampilkan pesan error.
     */
    public function test_TC05_08_import_file_format_tidak_valid_ditolak(): void
    {
        $staf = $this->buatStaf();
        $file = UploadedFile::fake()->create('data.exe', 10, 'application/octet-stream');

        $response = $this->actingAs($staf)
            ->post('/penduduk/import', ['file' => $file]);

        $this->assertTrue(
            session()->has('error') || $response->status() === 422,
            'File berformat .exe harus ditolak oleh sistem.'
        );
    }

    /**
     * TC05-09 (Alternatif): Impor file yang mengandung baris NIK duplikat.
     * Diharapkan: Sistem melewati/memperbarui baris duplikat dan tetap memproses data lain.
     */
    public function test_TC05_09_import_csv_nik_duplikat_diproses_tanpa_error(): void
    {
        $staf = $this->buatStaf();

        // Buat penduduk yang sudah ada dengan NIK tertentu
        $existing = Penduduk::create($this->dataPenduduk([
            'nik'  => '7301012501900001',
            'nama' => 'BUDI SANTOSO',
        ]));

        $csvContent = implode("\n", [
            'kode keluarga,nama,nik,jenis kelamin',
            // Baris duplikat (NIK sama)
            '7301012501000001,BUDI SANTOSO,7301012501900001,L',
            // Baris baru yang valid
            '7301012501000002,SITI RAHAYU,7301012501900002,P',
        ]);

        $file = $this->csvUploadFile($csvContent, 'penduduk_dup.csv');

        $response = $this->actingAs($staf)
            ->post('/penduduk/import', ['file' => $file]);

        // Import harus berhasil (tidak crash karena duplikat)
        $this->assertTrue(
            $response->isRedirection() || session()->has('success') || session()->has('error'),
            'Import dengan NIK duplikat harus tetap berjalan tanpa exception.'
        );
    }

    /**
     * TC05-10 (Normal): Pilih ekspor data penduduk.
     * Diharapkan: Sistem mengambil data dari basis data dan menghasilkan file ekspor (CSV).
     */
    public function test_TC05_10_ekspor_data_penduduk_menghasilkan_file_unduhan(): void
    {
        $staf = $this->buatStaf();
        Penduduk::create($this->dataPenduduk());

        $response = $this->actingAs($staf)->get('/penduduk/export');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('content-disposition', ''));
    }

    // ═══════════════════════════════════════════════════════
    // F. PEMBUATAN & PENGELOLAAN SURAT KELUAR — TC06
    // ═══════════════════════════════════════════════════════

    /**
     * TC06-01 (Normal): Buat surat dengan memilih data penduduk terkait.
     * Diharapkan: Sistem membuat surat dengan data penduduk yang sesuai.
     */
    public function test_TC06_01_buat_surat_dengan_data_penduduk_berhasil(): void
    {
        $staf     = $this->buatStaf();
        $penduduk = Penduduk::create($this->dataPenduduk([
            'nama' => 'BUDI SANTOSO',
        ]));

        $response = $this->actingAs($staf)->postJson('/surat/keterangan-domisili/finalize', [
            'title'      => 'Surat Keterangan Domisili',
            'index_code' => '474.1',
            'payload'    => [
                'penduduk_id' => $penduduk->id,
                'nama'        => 'budi santoso',
                'nik'         => $penduduk->nik,
                'alamat'      => $penduduk->alamat,
                'rt'          => $penduduk->rt,
                'rw'          => $penduduk->rw,
                'keperluan'   => 'Keperluan administrasi',
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('letters', [
            'template_slug' => 'keterangan-domisili',
            'title'         => 'Surat Keterangan Domisili',
        ]);
    }

    /**
     * TC06-02 (Normal): Buat surat baru dan amati nomor yang dihasilkan.
     * Diharapkan: Sistem menghasilkan nomor surat otomatis sesuai template, tanpa duplikasi.
     */
    public function test_TC06_02_penomoran_surat_otomatis_tanpa_duplikasi(): void
    {
        $staf     = $this->buatStaf();
        $penduduk = Penduduk::create($this->dataPenduduk([
            'nama' => 'ANI RAHAYU',
            'nik'  => '7301012501900099',
        ]));

        $payload = [
            'title'      => 'Surat Keterangan Domisili',
            'index_code' => '474.1',
            'payload'    => [
                'penduduk_id' => $penduduk->id,
                'nama'        => 'ani rahayu',
                'nik'         => $penduduk->nik,
                'alamat'      => 'Jl. Test',
                'rt'          => '001',
                'rw'          => '001',
                'keperluan'   => 'Keperluan',
            ],
        ];

        $resp1 = $this->actingAs($staf)->postJson('/surat/keterangan-domisili/finalize', $payload);
        $resp2 = $this->actingAs($staf)->postJson('/surat/keterangan-domisili/finalize', $payload);

        $resp1->assertOk();
        $resp2->assertOk();

        $noSurat1 = $resp1->json('noSurat');
        $noSurat2 = $resp2->json('noSurat');

        // Nomor surat harus berbeda (urut naik)
        $this->assertNotEquals($noSurat1, $noSurat2);
        // Format: {urut}/Kel.Ftbs.{indexCode}/{bulanRomawi}/{tahun}
        $this->assertMatchesRegularExpression('/^\d+\/Kel\.Ftbs\.\d+\.\d+\/[IVX]+\/\d{4}$/', $noSurat1);
        $this->assertMatchesRegularExpression('/^\d+\/Kel\.Ftbs\.\d+\.\d+\/[IVX]+\/\d{4}$/', $noSurat2);
    }

    /**
     * TC06-03 (Normal): Finalisasi surat, lalu cetak/unduh.
     * Diharapkan: Surat difinalisasi, dapat dicetak/diunduh, dan otomatis tersimpan ke arsip.
     */
    public function test_TC06_03_finalisasi_surat_tersimpan_ke_arsip(): void
    {
        $staf     = $this->buatStaf();
        $penduduk = Penduduk::create($this->dataPenduduk([
            'nama' => 'CITRA DEWI',
            'nik'  => '7301012501900088',
        ]));

        // Finalisasi surat
        $response = $this->actingAs($staf)->postJson('/surat/keterangan-domisili/finalize', [
            'title'      => 'Surat Keterangan Domisili',
            'index_code' => '474.1',
            'payload'    => [
                'penduduk_id' => $penduduk->id,
                'nama'        => 'citra dewi',
                'nik'         => $penduduk->nik,
                'alamat'      => 'Jl. Citra',
                'rt'          => '001',
                'rw'          => '001',
                'keperluan'   => 'Keperluan',
            ],
        ]);

        $response->assertOk();
        $letterId = $response->json('id');
        $this->assertNotNull($letterId);

        // Verifikasi surat tersimpan di arsip
        $this->assertDatabaseHas('letters', ['id' => $letterId]);

        // Surat dapat diakses melalui halaman arsip
        $this->actingAs($staf)->get('/arsip-surat')->assertOk();
    }

    /**
     * TC06-04 (Alternatif): Buat surat dengan data wajib belum lengkap.
     * Diharapkan: Sistem menampilkan pesan validasi dan tidak memfinalisasi surat.
     */
    public function test_TC06_04_finalisasi_surat_data_kosong_ditolak_422(): void
    {
        $staf = $this->buatStaf();

        $response = $this->actingAs($staf)
            ->postJson('/surat/keterangan-domisili/finalize', []);

        $response->assertStatus(422);
        $this->assertDatabaseCount('letters', 0);
    }

    // ═══════════════════════════════════════════════════════
    // G. ARSIP & PENCARIAN SURAT — TC07
    // ═══════════════════════════════════════════════════════

    /**
     * TC07-01 (Normal): Buka menu arsip surat setelah ada surat yang difinalisasi.
     * Diharapkan: Sistem menampilkan daftar arsip surat yang telah dibuat.
     */
    public function test_TC07_01_halaman_arsip_surat_menampilkan_daftar(): void
    {
        $admin = $this->buatAdmin();
        $this->buatLetterDb();

        $response = $this->actingAs($admin)->get('/arsip-surat');

        $response->assertOk();
    }

    /**
     * TC07-02 (Normal): Cari arsip berdasarkan nomor, jenis, atau rentang tanggal surat.
     * Diharapkan: Sistem menampilkan arsip surat yang sesuai dengan kriteria pencarian/filter.
     */
    public function test_TC07_02_pencarian_arsip_berdasarkan_filter_berhasil(): void
    {
        $admin = $this->buatAdmin();
        $this->buatLetterDb(['no_surat' => 'DOM/001/2026', 'title' => 'Surat Keterangan Domisili']);

        $byNoSurat = $this->actingAs($admin)->get('/arsip-surat?q=DOM/001');
        $byTitle   = $this->actingAs($admin)->get('/arsip-surat?q=Domisili');
        $byDate    = $this->actingAs($admin)->get('/arsip-surat?date_from=2026-01-01&date_to=2026-12-31');

        $byNoSurat->assertOk();
        $byTitle->assertOk();
        $byDate->assertOk();
    }

    /**
     * TC07-03 (Alternatif): Cari arsip dengan kriteria yang tidak ada datanya.
     * Diharapkan: Sistem menampilkan hasil kosong tanpa error.
     */
    public function test_TC07_03_pencarian_arsip_tidak_ada_data_tampil_kosong_tanpa_error(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)
            ->get('/arsip-surat?q=SURATYANGTIDAKADAXYZ99999');

        $response->assertOk();
        $response->assertDontSee('500');
    }

    // ═══════════════════════════════════════════════════════
    // H. SURAT MASUK & UNGGAH DOKUMEN — TC08
    // ═══════════════════════════════════════════════════════

    /**
     * TC08-01 (Normal): Tambah surat masuk manual dan unggah dokumen berformat valid.
     * Diharapkan: Sistem menyimpan surat masuk beserta dokumen, mengirim notifikasi.
     */
    public function test_TC08_01_tambah_surat_masuk_dokumen_valid_berhasil(): void
    {
        $staf  = $this->buatStaf();
        Storage::fake('public');

        $file = UploadedFile::fake()->create('surat_masuk.pdf', 100, 'application/pdf');

        $response = $this->actingAs($staf)->post('/arsip-surat', [
            'no_surat'    => 'SM/001/2026',
            'title'       => 'Surat Masuk Dinas Pendidikan',
            'manual_type' => 'masuk',
            'files'       => [$file],
        ]);

        $response->assertRedirect(route('arsip-surat.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('letters', [
            'no_surat'  => 'SM/001/2026',
            'is_manual' => true,
        ]);
        $this->assertDatabaseHas('letter_documents', ['doc_key' => 'surat_masuk']);
    }

    /**
     * TC08-02 (Alternatif): Unggah dokumen dengan tipe/ukuran tidak valid.
     * Diharapkan: Sistem menolak berkas dan menampilkan pesan error.
     */
    public function test_TC08_02_upload_dokumen_format_tidak_valid_ditolak(): void
    {
        $staf = $this->buatStaf();
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $response = $this->actingAs($staf)->post('/surat/dokumen/upload', [
            'file'      => $file,
            'doc_key'   => 'test_key',
            'doc_label' => 'Test Dokumen',
        ]);

        $response->assertStatus(422);
    }

    /**
     * TC08-03 (Normal): Tambahkan surat masuk baru, amati panel notifikasi Lurah/Staf.
     * Diharapkan: Sistem menampilkan notifikasi adanya surat/arsip baru kepada Lurah dan Staf.
     */
    public function test_TC08_03_tambah_surat_masuk_mengirim_notifikasi_ke_lurah(): void
    {
        $staf  = $this->buatStaf();
        $lurah = $this->buatLurah();
        Storage::fake('public');

        $file = UploadedFile::fake()->create('dokumen.pdf', 50, 'application/pdf');

        $this->actingAs($staf)->post('/arsip-surat', [
            'no_surat'    => 'SM/002/2026',
            'title'       => 'Surat Masuk Test Notifikasi',
            'manual_type' => 'masuk',
            'files'       => [$file],
        ]);

        // Notifikasi harus terkirim ke lurah
        $this->assertDatabaseHas('letter_notifications', [
            'user_id' => $lurah->id,
            'is_read' => false,
        ]);

        // Lurah dapat melihat notifikasi melalui endpoint
        $response = $this->actingAs($lurah)->get('/notifications');
        $response->assertOk();
    }

    // ═══════════════════════════════════════════════════════
    // I. DISPOSISI SURAT — TC09
    // ═══════════════════════════════════════════════════════

    /**
     * TC09-01 (Normal): Lurah membuka detail surat, memilih staf, menulis catatan, kirim disposisi.
     * Diharapkan: Sistem menyimpan disposisi (pending), mengirim notifikasi ke staf, konfirmasi berhasil.
     */
    public function test_TC09_01_lurah_kirim_disposisi_valid_berhasil(): void
    {
        $lurah  = $this->buatLurah();
        $staf   = $this->buatStaf();
        $letter = $this->buatLetterDb();

        $response = $this->actingAs($lurah)->post("/arsip-surat/{$letter->id}/disposisi", [
            'to_user_id' => $staf->id,
            'catatan'    => 'Harap ditindaklanjuti segera.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('letter_dispositions', [
            'letter_id'    => $letter->id,
            'from_user_id' => $lurah->id,
            'to_user_id'   => $staf->id,
            'status'       => 'pending',
        ]);

        $this->assertDatabaseHas('letter_notifications', [
            'user_id'   => $staf->id,
            'letter_id' => $letter->id,
            'is_read'   => false,
        ]);
    }

    /**
     * TC09-02 (Alternatif): Lurah mengirim disposisi tanpa memilih staf tujuan.
     * Diharapkan: Sistem menampilkan pesan validasi dan tidak mengirim disposisi.
     */
    public function test_TC09_02_disposisi_tanpa_staf_tujuan_ditolak_validasi(): void
    {
        $lurah  = $this->buatLurah();
        $letter = $this->buatLetterDb(['no_surat' => 'TEST/DISP/2026']);

        $response = $this->actingAs($lurah)->post("/arsip-surat/{$letter->id}/disposisi", [
            'to_user_id' => null,
            'catatan'    => 'Tanpa staf tujuan',
        ]);

        $response->assertSessionHasErrors('to_user_id');
        $this->assertDatabaseCount('letter_dispositions', 0);
    }

    // ═══════════════════════════════════════════════════════
    // J. DISPOSISI TUGAS — TC10
    // ═══════════════════════════════════════════════════════

    /**
     * TC10-01 (Normal): Staf membuka tugas disposisi dan menekan tombol "sudah diterima".
     * Diharapkan: Status berubah menjadi diterima dan notifikasi dikirim ke Lurah.
     */
    public function test_TC10_01_staf_konfirmasi_diterima_status_berubah_notifikasi_lurah(): void
    {
        $lurah      = $this->buatLurah();
        $staf       = $this->buatStaf();
        $letter     = $this->buatLetterDb();
        $disposisi  = $this->buatDisposisi($letter, $lurah, $staf, 'pending');

        $response = $this->actingAs($staf)
            ->patch("/disposisi-tugas/{$disposisi->id}/diterima");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('diterima', $disposisi->fresh()->status);

        // Notifikasi dikirim ke lurah
        $this->assertDatabaseHas('letter_notifications', [
            'user_id'   => $lurah->id,
            'letter_id' => $letter->id,
        ]);
    }

    /**
     * TC10-02 (Normal): Setelah menyelesaikan tugas, staf menekan tombol "sudah selesai".
     * Diharapkan: Disposisi tugas dihilangkan dari daftar dan notifikasi penyelesaian dikirim ke Lurah.
     */
    public function test_TC10_02_staf_tandai_selesai_status_berubah_notifikasi_lurah(): void
    {
        $lurah     = $this->buatLurah();
        $staf      = $this->buatStaf();
        $letter    = $this->buatLetterDb(['no_surat' => 'TEST/SELESAI/2026']);
        $disposisi = $this->buatDisposisi($letter, $lurah, $staf, 'diterima');

        $response = $this->actingAs($staf)
            ->patch("/disposisi-tugas/{$disposisi->id}/selesai");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('selesai', $disposisi->fresh()->status);

        // Notifikasi penyelesaian dikirim ke lurah
        $this->assertDatabaseHas('letter_notifications', [
            'user_id'   => $lurah->id,
            'letter_id' => $letter->id,
        ]);
    }

    /**
     * TC10-03 (Alternatif): Staf membuka menu disposisi saat tidak ada tugas.
     * Diharapkan: Sistem menampilkan pesan bahwa belum ada tugas disposisi yang diterima.
     */
    public function test_TC10_03_staf_buka_disposisi_tidak_ada_tugas_tampil_tanpa_error(): void
    {
        $staf = $this->buatStaf();

        $response = $this->actingAs($staf)->get('/disposisi-tugas');

        $response->assertOk();
        $response->assertDontSee('500');
    }

    /**
     * TC10-04 (Alternatif): Simulasikan kegagalan pembaruan status (tombol terima/selesai kondisi salah).
     * Diharapkan: Sistem menampilkan pesan kesalahan dan status tugas tidak berubah.
     */
    public function test_TC10_04_simulasi_kegagalan_pembaruan_status_tugas(): void
    {
        $lurah     = $this->buatLurah();
        $staf      = $this->buatStaf();
        $letter    = $this->buatLetterDb(['no_surat' => 'TEST/FAIL/2026']);
        $disposisi = $this->buatDisposisi($letter, $lurah, $staf, 'diterima');

        // Skenario 1: Coba markDiterima saat status sudah 'diterima'
        $response = $this->actingAs($staf)
            ->patch("/disposisi-tugas/{$disposisi->id}/diterima");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        // Status tidak berubah (tetap 'diterima')
        $this->assertEquals('diterima', $disposisi->fresh()->status);

        // Skenario 2: Coba markSelesai saat status masih 'pending'
        $disposisiPending = $this->buatDisposisi(
            $this->buatLetterDb(['no_surat' => 'TEST/FAIL2/2026']),
            $lurah,
            $staf,
            'pending'
        );

        $response2 = $this->actingAs($staf)
            ->patch("/disposisi-tugas/{$disposisiPending->id}/selesai");

        $response2->assertRedirect();
        $response2->assertSessionHas('error');
        $this->assertEquals('pending', $disposisiPending->fresh()->status);
    }

    /**
     * TC10-05 (Alternatif): Simulasikan kegagalan pengiriman notifikasi ke Lurah.
     * Diharapkan: Sistem tetap menyimpan perubahan status DAN menampilkan pesan notifikasi gagal.
     */
    public function test_TC10_05_simulasi_kegagalan_notifikasi_status_tetap_tersimpan(): void
    {
        $lurah     = $this->buatLurah();
        $staf      = $this->buatStaf();
        $letter    = $this->buatLetterDb(['no_surat' => 'TEST/NOTIF/2026']);
        $disposisi = $this->buatDisposisi($letter, $lurah, $staf, 'diterima');

        // Simulasi kegagalan notifikasi: hapus tabel letter_notifications sementara
        DB::statement('DROP TABLE IF EXISTS letter_notifications');

        $response = $this->actingAs($staf)
            ->patch("/disposisi-tugas/{$disposisi->id}/selesai");

        // Status HARUS berhasil diperbarui ke 'selesai' meskipun notifikasi gagal
        $this->assertEquals('selesai', $disposisi->fresh()->status,
            'Status harus berhasil diperbarui ke "selesai" meskipun notifikasi gagal.'
        );

        // Respons harus redirect sukses (bukan error 500) — penanganan graceful
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pesan sukses harus menyebutkan bahwa notifikasi gagal dikirim
        $this->assertStringContainsString(
            'Notifikasi ke Lurah gagal dikirim',
            session('success'),
            'Pesan sukses harus mencantumkan bahwa notifikasi ke Lurah gagal dikirim.'
        );
    }

    // ═══════════════════════════════════════════════════════
    // K. SURAT KELUAR & INTEGRASI DISPOSISI — TC11
    // ═══════════════════════════════════════════════════════

    /**
     * TC11-01 (Normal): Staf mencatat surat keluar manual dengan nomor dan berkas valid.
     * Diharapkan: Sistem menyimpan surat keluar ke arsip dan mengirim notifikasi ke Lurah.
     */
    public function test_TC11_01_catat_surat_keluar_valid_berhasil_disimpan(): void
    {
        $staf  = $this->buatStaf();
        $lurah = $this->buatLurah();
        Storage::fake('public');

        $file = UploadedFile::fake()->create('surat_keluar.pdf', 100, 'application/pdf');

        $response = $this->actingAs($staf)->post('/arsip-surat', [
            'no_surat'    => 'SK/001/2026',
            'title'       => 'Surat Keluar Balasan Kecamatan',
            'manual_type' => 'keluar',
            'files'       => [$file],
        ]);

        $response->assertRedirect(route('arsip-surat.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('letters', [
            'no_surat'    => 'SK/001/2026',
            'is_manual'   => true,
            'manual_type' => 'keluar',
        ]);
        $this->assertDatabaseHas('letter_documents', ['doc_key' => 'surat_keluar']);
        $this->assertDatabaseHas('letter_notifications', ['user_id' => $lurah->id]);
    }

    /**
     * TC11-02 (Alternatif): Catat surat keluar dengan nomor surat yang sudah ada di arsip.
     * Diharapkan: Sistem menolak dengan validasi nomor surat duplikat, tidak ada data baru tersimpan.
     */
    public function test_TC11_02_catat_surat_keluar_nomor_duplikat_ditolak(): void
    {
        $staf = $this->buatStaf();
        $this->buatLetterDb(['no_surat' => 'SK/DUP/2026']);

        $file = UploadedFile::fake()->create('surat_keluar.pdf', 100, 'application/pdf');

        $response = $this->actingAs($staf)->post('/arsip-surat', [
            'no_surat'    => 'SK/DUP/2026',
            'title'       => 'Surat Keluar Duplikat',
            'manual_type' => 'keluar',
            'files'       => [$file],
        ]);

        $response->assertSessionHasErrors('no_surat');
        $this->assertEquals(1, Letter::where('no_surat', 'SK/DUP/2026')->count());
    }

    /**
     * TC11-03 (Alternatif): Catat surat keluar dengan berkas berformat tidak sesuai (bukan
     * gambar/PDF). Diharapkan: Sistem menolak berkas dan tidak menyimpan surat ke arsip.
     */
    public function test_TC11_03_catat_surat_keluar_berkas_tidak_sesuai_ditolak(): void
    {
        $staf = $this->buatStaf();
        $file = UploadedFile::fake()->create('naskah.exe', 100, 'application/octet-stream');

        $response = $this->actingAs($staf)->post('/arsip-surat', [
            'no_surat'    => 'SK/002/2026',
            'title'       => 'Surat Keluar Berkas Salah',
            'manual_type' => 'keluar',
            'files'       => [$file],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('letters', ['no_surat' => 'SK/002/2026']);
    }

    /**
     * TC11-04 (Alternatif): Staf menyelesaikan tugas disposisi tanpa mencatat surat keluar
     * (memilih "Tidak" pada konfirmasi). Diharapkan: Tugas tetap berhasil ditandai selesai dan
     * hilang dari daftar tugas, tanpa mewajibkan adanya surat keluar yang dihasilkan.
     */
    public function test_TC11_04_penyelesaian_disposisi_tanpa_surat_keluar_tetap_berhasil(): void
    {
        $lurah     = $this->buatLurah();
        $staf      = $this->buatStaf();
        $letter    = $this->buatLetterDb(['no_surat' => 'TEST/TANPA-KELUAR/2026']);
        $disposisi = $this->buatDisposisi($letter, $lurah, $staf, 'diterima');

        $response = $this->actingAs($staf)
            ->patch("/disposisi-tugas/{$disposisi->id}/selesai");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('selesai', $disposisi->fresh()->status);

        // Tidak ada surat keluar yang otomatis tercatat — penyelesaian tugas tidak mewajibkannya
        $this->assertDatabaseMissing('letters', ['manual_type' => 'keluar']);

        // Tugas yang sudah selesai tidak lagi muncul di daftar disposisi staf
        $indexResponse = $this->actingAs($staf)->get('/disposisi-tugas');
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('TEST/TANPA-KELUAR/2026');
    }
}
