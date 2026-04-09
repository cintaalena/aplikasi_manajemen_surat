<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Home/Index');
    }

    public function metrics(Request $request)
    {
        // ==========================
        // 1) METRIK SURAT
        // ==========================
        $timeCol = $this->resolveLetterTimeColumn();

        $now          = now();
        $startOfDay   = $now->copy()->startOfDay();
        $startOfWeek  = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear  = $now->copy()->startOfYear();

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
                    'label'         => $this->labelTemplate($row->template_slug),
                    'total'         => (int) $row->total,
                ];
            });

        // ==========================
        // 3) METRIK PENDUDUK
        // ==========================
        $pendudukQuery = DB::table('penduduks')->whereNull('deleted_at');

        $jumlahJiwa = (clone $pendudukQuery)->count();

        $jumlahLakiLaki = (clone $pendudukQuery)
            ->where('jenis_kelamin', 'L')->count();

        $jumlahPerempuan = (clone $pendudukQuery)
            ->where('jenis_kelamin', 'P')->count();

        $jumlahRt = (clone $pendudukQuery)
            ->whereNotNull('rt')->where('rt', '!=', '')
            ->distinct('rt')->count('rt');

        $jumlahRw = (clone $pendudukQuery)
            ->whereNotNull('rw')->where('rw', '!=', '')
            ->distinct('rw')->count('rw');

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
            })->count();

        $totalIslam = (clone $pendudukQuery)
            ->whereNotNull('agama')
            ->where('agama', 'like', '%Islam%')->count();

        $totalKatholik = (clone $pendudukQuery)
            ->whereNotNull('agama')
            ->where(function ($q) {
                $q->where('agama', 'like', '%Katholik%')
                  ->orWhere('agama', 'like', '%Katolik%');
            })->count();

        $totalHindu = (clone $pendudukQuery)
            ->whereNotNull('agama')
            ->where('agama', 'like', '%Hindu%')->count();

        $totalBuddha = (clone $pendudukQuery)
            ->whereNotNull('agama')
            ->where(function ($q) {
                $q->where('agama', 'like', '%Buddha%')
                  ->orWhere('agama', 'like', '%Budha%');
            })->count();

        $totalKonghucu = (clone $pendudukQuery)
            ->whereNotNull('agama')
            ->where(function ($q) {
                $q->where('agama', 'like', '%Konghucu%')
                  ->orWhere('agama', 'like', '%Khonghucu%');
            })->count();

        // ==========================
        // 5) GENDER SEMUA JIWA PER RT/RW (fitur sebelumnya, tetap ada)
        // ==========================
        $genderPerRtRw = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereNotNull('rt')->where('rt', '!=', '')
            ->whereNotNull('rw')->where('rw', '!=', '')
            ->select(
                'rw', 'rt',
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki_laki"),
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('rw', 'rt')
            ->orderByRaw('CAST(rw AS UNSIGNED) ASC, CAST(rt AS UNSIGNED) ASC')
            ->get()
            ->map(fn($row) => [
                'rw'        => $row->rw,
                'rt'        => $row->rt,
                'laki_laki' => (int) $row->laki_laki,
                'perempuan' => (int) $row->perempuan,
                'total'     => (int) $row->total,
            ]);

        $subtotalPerRw = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereNotNull('rw')->where('rw', '!=', '')
            ->select(
                'rw',
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki_laki"),
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('rw')
            ->orderByRaw('CAST(rw AS UNSIGNED) ASC')
            ->get()
            ->map(fn($row) => [
                'rw'        => $row->rw,
                'laki_laki' => (int) $row->laki_laki,
                'perempuan' => (int) $row->perempuan,
                'total'     => (int) $row->total,
            ]);

        // ==========================
        // 6) KEPALA KELUARGA PER RT/RW BERDASARKAN JENIS KELAMIN KK
        //
        // Logika:
        //   - Kepala Keluarga diidentifikasi dari kolom `hubungan = 'Kepala Keluarga'`
        //     ATAU `no_urut = 1` (sebagai fallback).
        //   - Satu kode_keluarga = satu KK. Jika ada duplikasi (2 baris no_urut=1
        //     untuk KK yang sama), kita ambil baris dengan MIN(id) saja.
        //   - Dari baris KK tersebut, kita baca `jenis_kelamin` untuk tahu
        //     apakah KK itu laki-laki atau perempuan.
        //   - Lalu GROUP BY rw, rt untuk tampilan per RT/RW.
        // ==========================

        // ── Sub-query: satu baris representatif per kode_keluarga (level RT/RW) ──
        $kkSubqueryRtRw = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereNotNull('kode_keluarga')->where('kode_keluarga', '!=', '')
            ->whereNotNull('rt')->where('rt', '!=', '')
            ->whereNotNull('rw')->where('rw', '!=', '')
            ->where(function ($q) {
                $q->where('hubungan', 'Kepala Keluarga')
                  ->orWhere('no_urut', 1);
            })
            ->select(
                DB::raw('MIN(id) as id'),   // ambil 1 baris per KK
                'kode_keluarga',
                'rw',
                'rt',
                'jenis_kelamin'
            )
            ->groupBy('kode_keluarga', 'rw', 'rt', 'jenis_kelamin');

        // ── Query utama: hitung KK L/P per RT/RW ──
        $kkPerRtRw = DB::table(DB::raw("({$kkSubqueryRtRw->toSql()}) as kk_rt_rw"))
            ->mergeBindings($kkSubqueryRtRw)
            ->select(
                'rw',
                'rt',
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as kk_laki_laki"),
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as kk_perempuan"),
                DB::raw('COUNT(*) as total_kk')
            )
            ->groupBy('rw', 'rt')
            ->orderByRaw('CAST(rw AS UNSIGNED) ASC, CAST(rt AS UNSIGNED) ASC')
            ->get()
            ->map(fn($row) => [
                'rw'           => $row->rw,
                'rt'           => $row->rt,
                'kk_laki_laki' => (int) $row->kk_laki_laki,
                'kk_perempuan' => (int) $row->kk_perempuan,
                'total_kk'     => (int) $row->total_kk,
            ]);

        // ── Sub-total KK per RW ──
        $kkSubqueryRw = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereNotNull('kode_keluarga')->where('kode_keluarga', '!=', '')
            ->whereNotNull('rw')->where('rw', '!=', '')
            ->where(function ($q) {
                $q->where('hubungan', 'Kepala Keluarga')
                  ->orWhere('no_urut', 1);
            })
            ->select(
                DB::raw('MIN(id) as id'),
                'kode_keluarga',
                'rw',
                'jenis_kelamin'
            )
            ->groupBy('kode_keluarga', 'rw', 'jenis_kelamin');

        $kkSubtotalPerRw = DB::table(DB::raw("({$kkSubqueryRw->toSql()}) as kk_rw"))
            ->mergeBindings($kkSubqueryRw)
            ->select(
                'rw',
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as kk_laki_laki"),
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as kk_perempuan"),
                DB::raw('COUNT(*) as total_kk')
            )
            ->groupBy('rw')
            ->orderByRaw('CAST(rw AS UNSIGNED) ASC')
            ->get()
            ->map(fn($row) => [
                'rw'           => $row->rw,
                'kk_laki_laki' => (int) $row->kk_laki_laki,
                'kk_perempuan' => (int) $row->kk_perempuan,
                'total_kk'     => (int) $row->total_kk,
            ]);

        // ── Grand total KK seluruh kelurahan ──
        $kkSubqueryGrand = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereNotNull('kode_keluarga')->where('kode_keluarga', '!=', '')
            ->where(function ($q) {
                $q->where('hubungan', 'Kepala Keluarga')
                  ->orWhere('no_urut', 1);
            })
            ->select(
                DB::raw('MIN(id) as id'),
                'kode_keluarga',
                'jenis_kelamin'
            )
            ->groupBy('kode_keluarga', 'jenis_kelamin');

        $kkGrandTotal = DB::table(DB::raw("({$kkSubqueryGrand->toSql()}) as kk_grand"))
            ->mergeBindings($kkSubqueryGrand)
            ->select(
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as kk_laki_laki"),
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as kk_perempuan"),
                DB::raw('COUNT(*) as total_kk')
            )
            ->first();

        return response()->json([
            'letters' => [
                'today' => $lettersToday,
                'week'  => $lettersWeek,
                'month' => $lettersMonth,
                'year'  => $lettersYear,
            ],
            'top_templates_30d' => $topTemplates,
            'population' => [
                'jumlah_rt'              => $jumlahRt,
                'jumlah_rw'              => $jumlahRw,
                'total_laki_laki'        => $jumlahLakiLaki,
                'total_perempuan'        => $jumlahPerempuan,
                'jumlah_jiwa'            => $jumlahJiwa,
                'jumlah_kepala_keluarga' => $jumlahKepalaKeluarga,
                'total_kk'               => $totalKk,
            ],
            'agama' => [
                'kristen'  => $totalKristen,
                'islam'    => $totalIslam,
                'katholik' => $totalKatholik,
                'hindu'    => $totalHindu,
                'buddha'   => $totalBuddha,
                'konghucu' => $totalKonghucu,
            ],
            // pengelompokan gender semua jiwa per RT/RW
            'gender_per_rt_rw' => $genderPerRtRw,
            'subtotal_per_rw'  => $subtotalPerRw,
            // ── BARU: pengelompokan KEPALA KELUARGA per RT/RW ──
            'kk_per_rt_rw'       => $kkPerRtRw,
            'kk_subtotal_per_rw' => $kkSubtotalPerRw,
            'kk_grand_total'     => [
                'kk_laki_laki' => (int) ($kkGrandTotal->kk_laki_laki ?? 0),
                'kk_perempuan' => (int) ($kkGrandTotal->kk_perempuan ?? 0),
                'total_kk'     => (int) ($kkGrandTotal->total_kk ?? 0),
            ],
            'meta' => [
                'time_column'  => $timeCol,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function resolveLetterTimeColumn(): string
    {
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
        $map = [
            'keterangan-domisili'      => 'Surat Keterangan Domisili',
            'keterangan-kematian'      => 'Surat Keterangan Kematian',
            'keterangan-kelahiran'     => 'Surat Keterangan Kelahiran',
            'keterangan-usaha'         => 'Surat Keterangan Usaha',
            'keterangan-kelakuan-baik' => 'Surat Keterangan Kelakuan Baik',
            'keterangan-umum'          => 'Surat Keterangan (Umum)',
        ];
        return $map[$slug] ?? $slug;
    }
}