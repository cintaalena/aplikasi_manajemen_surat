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
        // Pilih kolom waktu yang dipakai
        // Jika sistem Anda punya printed_at, pakai itu.
        // Kalau belum, ganti ke 'created_at'
        $timeCol = $this->resolveLetterTimeColumn();

        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek(); // default Laravel: Monday
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
        // 3) JUMLAH PENDUDUK (DARI DB PENDUDUK)
        // ==========================
        // Karena data dummy Anda saat ini “per KK”, kita bisa tampilkan:
        // - total_kk = jumlah row penduduks (1 row = 1 KK)
        // Kalau Anda sudah punya tabel anggota keluarga/individu, nanti tinggal ganti.
        $totalKk = DB::table('penduduks')->count();

        return response()->json([
            'letters' => [
                'today' => $lettersToday,
                'week' => $lettersWeek,
                'month' => $lettersMonth,
                'year' => $lettersYear,
            ],
            'top_templates_30d' => $topTemplates,
            'population' => [
                'total_kk' => $totalKk,
                // placeholder kalau nanti Anda punya total individu:
                'total_penduduk' => $this->estimatePopulationFromKk($totalKk),
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

    private function estimatePopulationFromKk(int $totalKk): int
    {
        // Karena dummy Anda per KK, kalau butuh angka "penduduk" sekarang,
        // kita bisa pakai estimasi rata-rata anggota KK (misal 4).
        // Nanti kalau Anda punya tabel individu, hapus ini.
        return $totalKk * 4;
    }
}
