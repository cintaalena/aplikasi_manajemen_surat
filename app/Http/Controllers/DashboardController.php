<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Render halaman Home/Dashboard (Vue)
        return Inertia::render('Home/Index');
    }

    public function metrics(Request $request)
{
    // ==========================
    // 1) METRIK SURAT
    // ==========================
    $timeCol = $this->resolveLetterTimeColumn();

    $now = now();
    $startOfDay = $now->copy()->startOfDay();
    $startOfWeek = $now->copy()->startOfWeek();
    $startOfMonth = $now->copy()->startOfMonth();
    $startOfYear = $now->copy()->startOfYear();

    $lettersToday = DB::table('letters')->where($timeCol, '>=', $startOfDay)->count();
    $lettersWeek  = DB::table('letters')->where($timeCol, '>=', $startOfWeek)->count();
    $lettersMonth = DB::table('letters')->where($timeCol, '>=', $startOfMonth)->count();
    $lettersYear  = DB::table('letters')->where($timeCol, '>=', $startOfYear)->count();

    // ==========================
    // 2) TOP SURAT 30 HARI TERAKHIR
    // ==========================
    $since30 = $now->copy()->subDays(30);

    $topTemplates = DB::table('letters')
        ->select('template_slug', DB::raw('COUNT(*) as total'))
        ->where($timeCol, '>=', $since30)
        ->groupBy('template_slug')
        ->orderByDesc('total')
        ->limit(10)
        ->get()
        ->map(function ($row) {
            return [
                'template_slug' => $row->template_slug,
                'label' => $this->labelTemplate($row->template_slug),
                'total' => (int) $row->total,
            ];
        });

    // ==========================
    // 3) METRIK PENDUDUK
    // ==========================
    $pendudukQuery = DB::table('penduduks')->whereNull('deleted_at');

    $jumlahJiwa = (clone $pendudukQuery)->count();

    $jumlahLakiLaki = (clone $pendudukQuery)
        ->where('jenis_kelamin', 'L')
        ->count();

    $jumlahPerempuan = (clone $pendudukQuery)
        ->where('jenis_kelamin', 'P')
        ->count();

    $jumlahRt = (clone $pendudukQuery)
        ->whereNotNull('rt')
        ->where('rt', '!=', '')
        ->distinct('rt')
        ->count('rt');

    $jumlahRw = (clone $pendudukQuery)
        ->whereNotNull('rw')
        ->where('rw', '!=', '')
        ->distinct('rw')
        ->count('rw');

    $jumlahKepalaKeluarga = (clone $pendudukQuery)
        ->whereNotNull('kode_keluarga')
        ->where('kode_keluarga', '!=', '')
        ->where(function ($q) {
            $q->where('hubungan', 'Kepala Keluarga')
              ->orWhere('no_urut', 1);
        })
        ->distinct('kode_keluarga')
        ->count('kode_keluarga');

    $totalKk = (clone $pendudukQuery)
        ->whereNotNull('kode_keluarga')
        ->where('kode_keluarga', '!=', '')
        ->distinct('kode_keluarga')
        ->count('kode_keluarga');

    // ==========================
    // 4) METRIK AGAMA
    // ==========================
    $totalKristen = (clone $pendudukQuery)
        ->whereNotNull('agama')
        ->where(function ($q) {
            $q->where('agama', 'like', '%Kristen%')
              ->orWhere('agama', 'like', '%Kristen Protestan%');
        })
        ->count();

    $totalIslam = (clone $pendudukQuery)
        ->whereNotNull('agama')
        ->where('agama', 'like', '%Islam%')
        ->count();

    $totalKatholik = (clone $pendudukQuery)
        ->whereNotNull('agama')
        ->where(function ($q) {
            $q->where('agama', 'like', '%Katholik%')
              ->orWhere('agama', 'like', '%Katolik%');
        })
        ->count();

    $totalHindu = (clone $pendudukQuery)
        ->whereNotNull('agama')
        ->where('agama', 'like', '%Hindu%')
        ->count();

    $totalBuddha = (clone $pendudukQuery)
        ->whereNotNull('agama')
        ->where(function ($q) {
            $q->where('agama', 'like', '%Buddha%')
              ->orWhere('agama', 'like', '%Budha%');
        })
        ->count();

    $totalKonghucu = (clone $pendudukQuery)
        ->whereNotNull('agama')
        ->where(function ($q) {
            $q->where('agama', 'like', '%Konghucu%')
              ->orWhere('agama', 'like', '%Khonghucu%');
        })
        ->count();

    return response()->json([
        'letters' => [
            'today' => $lettersToday,
            'week' => $lettersWeek,
            'month' => $lettersMonth,
            'year' => $lettersYear,
        ],
        'top_templates_30d' => $topTemplates,
        'population' => [
            'jumlah_rt' => $jumlahRt,
            'jumlah_rw' => $jumlahRw,
            'total_laki_laki' => $jumlahLakiLaki,
            'total_perempuan' => $jumlahPerempuan,
            'jumlah_jiwa' => $jumlahJiwa,
            'jumlah_kepala_keluarga' => $jumlahKepalaKeluarga,
            'total_kk' => $totalKk,
        ],
        'agama' => [
            'kristen' => $totalKristen,
            'islam' => $totalIslam,
            'katholik' => $totalKatholik,
            'hindu' => $totalHindu,
            'buddha' => $totalBuddha,
            'konghucu' => $totalKonghucu,
        ],
        'meta' => [
            'time_column' => $timeCol,
            'generated_at' => now()->toIso8601String(),
        ],
    ]);
}

    private function resolveLetterTimeColumn(): string
    {
        // Cek cepat: kalau kolom printed_at ada, pakai. Kalau tidak, pakai created_at.
        // Ini aman tanpa schema builder juga, tapi paling robust pakai Schema::hasColumn.
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('letters', 'printed_at')) {
                return 'printed_at';
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return 'created_at';
    }

    private function labelTemplate(?string $slug): string
    {
        if (!$slug) return '-';

        // Mapping label template (sesuaikan dengan daftar template Anda)
        $map = [
            'keterangan-domisili' => 'Surat Keterangan Domisili',
            'keterangan-kematian' => 'Surat Keterangan Kematian',
            'keterangan-kelahiran' => 'Surat Keterangan Kelahiran',
            'keterangan-usaha' => 'Surat Keterangan Usaha',
            'keterangan-kelakuan-baik' => 'Surat Keterangan Kelakuan Baik',
            'keterangan-umum' => 'Surat Keterangan (Umum)',
        ];

        return $map[$slug] ?? $slug;
    }
}
