<?php

/**
 * Functional Testing — Aplikasi Manajemen Surat Kelurahan Fatubesi
 * TC01 s.d. TC10 sesuai dokumen test case skripsi.
 *
 * Jalankan: php artisan test --filter FunctionalTest
 */

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\Penduduk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'name'                 => 'SuperAdmin',
            'email'                => 'admin@test.local',
            'password'             => Hash::make('Admin123!'),
            'credential_code_hash' => null,
            'jabatan'              => 'admin',
            'role'                 => 'admin',
            'is_active'            => true,
            'email_verified_at'    => now(),
        ]);
    }

    private function buatStaf(string $kode = 'B-001'): User
    {
        return User::create([
            'name'                 => 'STAF TEST',
            'email'                => 'staf@test.local',
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

    // ═══════════════════════════════════════
    // A. LOGIN — TC01
    // ═══════════════════════════════════════

    /** @test TC01-01: Login admin dengan kredensial valid */
    public function test_TC01_01_login_admin_valid_berhasil_masuk_dashboard()
    {
        $this->buatAdmin();

        $this->post('/login', ['name' => 'SuperAdmin', 'password' => 'Admin123!'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    /** @test TC01-02: Login dengan password salah */
    public function test_TC01_02_login_password_salah_ditolak_pesan_generik()
    {
        $this->buatAdmin();

        $this->post('/login', ['name' => 'SuperAdmin', 'password' => 'salah123'])
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    /** @test TC01-03: Login staf dengan credential_code salah */
    public function test_TC01_03_login_credential_code_salah_ditolak()
    {
        $this->buatStaf('B-001');

        $this->post('/login', [
            'name'            => 'STAF TEST',
            'password'        => 'Password123!',
            'credential_code' => 'SALAH',
        ])->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    /** @test TC01-04: Login dengan kolom wajib kosong */
    public function test_TC01_04_login_kolom_kosong_ditolak_validasi()
    {
        $this->post('/login', ['name' => '', 'password' => ''])
            ->assertSessionHasErrors(['name', 'password']);

        $this->assertGuest();
    }

    /** @test TC01-05: Akses URL internal tanpa login */
    public function test_TC01_05_akses_url_internal_tanpa_login_diarahkan_ke_login()
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/penduduk')->assertRedirect('/login');
        $this->get('/arsip-surat')->assertRedirect('/login');
    }

    // ═══════════════════════════════════════
    // B. LOGOUT — TC02
    // ═══════════════════════════════════════

    /** @test TC02-01: Logout menghapus sesi */
    public function test_TC02_01_logout_menghapus_sesi_dan_redirect_ke_home()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test TC02-02: Akses URL setelah logout diarahkan ke login */
    public function test_TC02_02_akses_url_setelah_logout_diarahkan_ke_login()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post('/logout');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // ═══════════════════════════════════════
    // C. DASHBOARD — TC03
    // ═══════════════════════════════════════

    /** @test TC03-01: Dashboard tampil setelah login */
    public function test_TC03_01_dashboard_tampil_setelah_login()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    /** @test TC03-02: Dashboard tampil normal saat data belum tersedia */
    public function test_TC03_02_dashboard_tampil_normal_tanpa_data()
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        $response->assertDontSee('500');
    }

    /** @test TC03-04: Export rekapitulasi ke Excel */
    public function test_TC03_04_export_rekapitulasi_menghasilkan_file_excel()
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get('/dashboard/export-excel');
        $response->assertOk();
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition', ''), 'attachment') ||
            str_contains($response->headers->get('content-type', ''), 'spreadsheet') ||
            str_contains($response->headers->get('content-type', ''), 'excel')
        );
    }

    // ═══════════════════════════════════════
    // D. PENGELOLAAN PENGGUNA — TC04
    // ═══════════════════════════════════════

    /** @test TC04-01: Admin menambah pengguna baru */
    public function test_TC04_01_admin_tambah_pengguna_baru_berhasil()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name'                  => 'Staf Baru',
            'jabatan'               => 'Staf Pelayanan',
            'role'                  => 'staff',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['name' => 'STAF BARU']);
    }

    /** @test TC04-03: Admin tambah pengguna tanpa peran */
    public function test_TC04_03_admin_tambah_pengguna_tanpa_peran_ditolak()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name'                  => 'Tanpa Peran',
            'jabatan'               => 'Staf',
            'role'                  => '',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('role');
    }

    /** @test TC04-04: Admin simpan pengguna dengan kolom kosong */
    public function test_TC04_04_admin_simpan_pengguna_kolom_kosong_ditolak()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post('/admin/pengguna', [
            'name' => '', 'jabatan' => '', 'role' => '', 'password' => '',
        ])->assertSessionHasErrors(['name', 'jabatan', 'role', 'password']);
    }

    /** @test TC04-05: Admin menonaktifkan akun pengguna */
    public function test_TC04_05_admin_nonaktifkan_pengguna_berhasil()
    {
        $admin = $this->buatAdmin();
        $staf  = $this->buatStaf();

        $this->actingAs($admin)
            ->patch("/admin/pengguna/{$staf->id}/toggle-active")
            ->assertRedirect();

        $this->assertFalse($staf->fresh()->is_active);
    }

    /** @test TC04-06: Staf akses menu admin ditolak */
    public function test_TC04_06_staf_akses_menu_admin_ditolak_403()
    {
        $staf = $this->buatStaf();

        $this->actingAs($staf)->get('/admin/pengguna')->assertForbidden();
    }

    // ═══════════════════════════════════════
    // E. DATA PENDUDUK — TC05
    // ═══════════════════════════════════════

    /** @test TC05-01: Tambah penduduk valid berhasil */
    public function test_TC05_01_tambah_penduduk_valid_berhasil()
    {
        $staf = $this->buatStaf();

        $this->actingAs($staf)
            ->post('/penduduk', array_merge($this->dataPenduduk(), ['is_kepala_keluarga' => true]))
            ->assertRedirect();

        $this->assertDatabaseHas('penduduks', ['nik' => '7301012501900001']);
    }

    /** @test TC05-02: Tambah penduduk dengan NIK duplikat ditolak */
    public function test_TC05_02_tambah_penduduk_nik_duplikat_ditolak()
    {
        $staf = $this->buatStaf();
        Penduduk::create($this->dataPenduduk());

        $this->actingAs($staf)
            ->post('/penduduk', $this->dataPenduduk(['nama' => 'NAMA LAIN']))
            ->assertSessionHasErrors('nik');
    }

    /** @test TC05-03: Tambah penduduk kolom wajib kosong ditolak */
    public function test_TC05_03_tambah_penduduk_kolom_wajib_kosong_ditolak()
    {
        $staf = $this->buatStaf();

        $this->actingAs($staf)
            ->post('/penduduk', ['nik' => '', 'nama' => '', 'jenis_kelamin' => ''])
            ->assertSessionHasErrors(['nik', 'nama', 'jenis_kelamin']);
    }

    /** @test TC05-04: Cari penduduk dengan kata kunci sesuai */
    public function test_TC05_04_cari_penduduk_kata_kunci_sesuai_menampilkan_hasil()
    {
        $staf = $this->buatStaf();
        Penduduk::create($this->dataPenduduk(['nama' => 'BUDI SANTOSO']));

        $this->actingAs($staf)
            ->getJson('/penduduk/search-by-name?q=BUDI')
            ->assertOk()
            ->assertJsonFragment(['nama' => 'BUDI SANTOSO']);
    }

    /** @test TC05-05: Cari penduduk kata kunci tidak ada */
    public function test_TC05_05_cari_penduduk_tidak_ditemukan_mengembalikan_kosong()
    {
        $staf = $this->buatStaf();

        $this->actingAs($staf)
            ->getJson('/penduduk/search-by-name?q=XYZNOTFOUND999')
            ->assertOk()
            ->assertExactJson([]);
    }

    /** @test TC05-06: Hapus penduduk berhasil */
    public function test_TC05_06_hapus_penduduk_berhasil_soft_delete()
    {
        $staf     = $this->buatStaf();
        $penduduk = Penduduk::create($this->dataPenduduk());

        $this->actingAs($staf)
            ->delete("/penduduk/{$penduduk->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('penduduks', ['id' => $penduduk->id]);
    }

    /** @test TC05-08: Import file format tidak valid ditolak */
    public function test_TC05_08_import_file_format_tidak_valid_ditolak()
    {
        $staf = $this->buatStaf();
        $file = UploadedFile::fake()->create('data.exe', 10, 'application/octet-stream');

        $this->actingAs($staf)
            ->post('/penduduk/import', ['file' => $file])
            ->assertSessionHas('error');
    }

    /** @test TC05-10: Export data penduduk menghasilkan file */
    public function test_TC05_10_export_penduduk_menghasilkan_file_unduhan()
    {
        $staf = $this->buatStaf();
        Penduduk::create($this->dataPenduduk());

        $response = $this->actingAs($staf)->get('/penduduk/export');
        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('content-disposition'));
    }

    // ═══════════════════════════════════════
    // F. PEMBUATAN SURAT — TC06
    // ═══════════════════════════════════════

    /** @test TC06-04: Finalisasi surat data tidak lengkap ditolak */
    public function test_TC06_04_finalisasi_surat_data_kosong_ditolak_422()
    {
        $staf = $this->buatStaf();

        $this->actingAs($staf)
            ->postJson('/surat/domisili/finalize', [])
            ->assertStatus(422);
    }

    // ═══════════════════════════════════════
    // G. ARSIP SURAT — TC07
    // ═══════════════════════════════════════

    /** @test TC07-01: Halaman arsip surat dapat diakses */
    public function test_TC07_01_halaman_arsip_surat_dapat_diakses()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->get('/arsip-surat')->assertOk();
    }

    /** @test TC07-02: Pencarian arsip sesuai filter */
    public function test_TC07_02_pencarian_arsip_berdasarkan_filter_berhasil()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get('/arsip-surat?search=domisili')
            ->assertOk();
    }

    /** @test TC07-03: Pencarian arsip hasil kosong tidak error */
    public function test_TC07_03_pencarian_arsip_tidak_ada_data_tampil_tanpa_error()
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get('/arsip-surat?search=SURATYANGTIDAKADA99999')
            ->assertOk();
    }

    // ═══════════════════════════════════════
    // H. UNGGAH DOKUMEN — TC08
    // ═══════════════════════════════════════

    /** @test TC08-02: Upload dokumen format tidak valid ditolak */
    public function test_TC08_02_upload_dokumen_format_tidak_valid_ditolak()
    {
        $staf = $this->buatStaf();
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $this->actingAs($staf)
            ->post('/surat/dokumen/upload', [
                'file'      => $file,
                'doc_key'   => 'test_key',
                'doc_label' => 'Test Dokumen',
            ])->assertStatus(422);
    }

    // ═══════════════════════════════════════
    // I. DISPOSISI SURAT — TC09
    // ═══════════════════════════════════════

    /** @test TC09-02: Lurah kirim disposisi tanpa staf tujuan ditolak */
    public function test_TC09_02_disposisi_tanpa_staf_tujuan_ditolak_validasi()
    {
        $lurah = $this->buatLurah();

        // Buat letter dummy langsung via DB
        $letterId = \Illuminate\Support\Facades\DB::table('letters')->insertGetId([
            'template_slug' => 'domisili',
            'title'         => 'Surat Keterangan Domisili',
            'no_surat'      => 'TEST/001/2026',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->actingAs($lurah)
            ->post("/arsip-surat/{$letterId}/disposisi", [
                'to_user_id' => null,
                'catatan'    => 'Tanpa staf tujuan',
            ])->assertSessionHasErrors('to_user_id');
    }

    // ═══════════════════════════════════════
    // J. DISPOSISI TUGAS — TC10
    // ═══════════════════════════════════════

    /** @test TC10-03: Staf buka disposisi saat tidak ada tugas */
    public function test_TC10_03_staf_buka_disposisi_tidak_ada_tugas_tampil_tanpa_error()
    {
        $staf = $this->buatStaf();

        $this->actingAs($staf)->get('/disposisi-tugas')->assertOk();
    }
}
