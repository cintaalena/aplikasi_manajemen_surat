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
        ->where('hubungan', 'Kepala Keluarga')
        ->whereNotNull('kode_keluarga')
        ->where('kode_keluarga', '!=', '')
        ->distinct()
        ->count('kode_keluarga');

    $totalKk = $jumlahKepalaKeluarga;

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
        // 6) KEPALA KELUARGA PER RT/RW
        // Logika baru:
        // - Hanya ambil data dengan hubungan = 'Kepala Keluarga'
        // - Satu kode_keluarga dihitung satu kali
        // - Jika ada duplikasi data kepala keluarga untuk 1 kode_keluarga,
        //   ambil satu baris representatif dengan MIN(id)
        // ==========================

        // Subquery dasar: satu kepala keluarga per kode_keluarga
        $kepalaKeluargaBase = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->where('hubungan', 'Kepala Keluarga')
            ->whereNotNull('kode_keluarga')->where('kode_keluarga', '!=', '')
            ->whereNotNull('rt')->where('rt', '!=', '')
            ->whereNotNull('rw')->where('rw', '!=', '')
            ->select(
                DB::raw('MIN(id) as id'),
                'kode_keluarga'
            )
            ->groupBy('kode_keluarga');

        // Ambil data lengkap kepala keluarga yang sudah dibersihkan dari duplikasi
        $kepalaKeluargaRtRw = DB::table('penduduks as p')
            ->joinSub($kepalaKeluargaBase, 'kk', function ($join) {
                $join->on('p.id', '=', 'kk.id');
            })
            ->select(
                'p.kode_keluarga',
                'p.rw',
                'p.rt',
                'p.jenis_kelamin'
            );

        // Hitung kepala keluarga per RT/RW
        $kkPerRtRw = DB::table(DB::raw("({$kepalaKeluargaRtRw->toSql()}) as kepala_keluarga_rt_rw"))
            ->mergeBindings($kepalaKeluargaRtRw)
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

        // Subtotal kepala keluarga per RW
        $kkSubtotalPerRw = DB::table(DB::raw("({$kepalaKeluargaRtRw->toSql()}) as kepala_keluarga_rw"))
            ->mergeBindings($kepalaKeluargaRtRw)
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

        // Grand total kepala keluarga seluruh kelurahan
        $kkGrandTotal = DB::table(DB::raw("({$kepalaKeluargaRtRw->toSql()}) as kepala_keluarga_grand"))
            ->mergeBindings($kepalaKeluargaRtRw)
            ->select(
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as kk_laki_laki"),
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as kk_perempuan"),
                DB::raw('COUNT(*) as total_kk')
            )
            ->first();

                // ==========================
        // 7) KELOMPOK UMUR
        // Berdasarkan tanggal_lahir dan jenis_kelamin
        // ==========================
        $ageRanges = [
            ['label' => '00-05',      'min' => 0,  'max' => 5],
            ['label' => '06-10',      'min' => 6,  'max' => 10],
            ['label' => '11-15',      'min' => 11, 'max' => 15],
            ['label' => '16-20',      'min' => 16, 'max' => 20],
            ['label' => '21-25',      'min' => 21, 'max' => 25],
            ['label' => '26-30',      'min' => 26, 'max' => 30],
            ['label' => '31-35',      'min' => 31, 'max' => 35],
            ['label' => '36-40',      'min' => 36, 'max' => 40],
            ['label' => '41-45',      'min' => 41, 'max' => 45],
            ['label' => '46-50',      'min' => 46, 'max' => 50],
            ['label' => '51-55',      'min' => 51, 'max' => 55],
            ['label' => '56-60',      'min' => 56, 'max' => 60],
            ['label' => '61 ke atas', 'min' => 61, 'max' => null],
        ];

        // Siapkan wadah hasil awal agar semua kelompok umur selalu tampil
        $ageGroupStats = [];
        foreach ($ageRanges as $range) {
            $ageGroupStats[$range['label']] = [
                'kelompok_umur' => $range['label'],
                'laki_laki'     => 0,
                'perempuan'     => 0,
                'jumlah'        => 0,
                'sex_rasio'     => 0,
            ];
        }

        // Ambil data umur dari tanggal_lahir
        $ageRows = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereNotNull('tanggal_lahir')
            ->whereDate('tanggal_lahir', '<=', now()->toDateString())
            ->whereIn('jenis_kelamin', ['L', 'P'])
            ->select(
                'jenis_kelamin',
                DB::raw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) as usia')
            )
            ->get();

        foreach ($ageRows as $row) {
            $usia = (int) $row->usia;
            $jk   = $row->jenis_kelamin;

            foreach ($ageRanges as $range) {
                $min = $range['min'];
                $max = $range['max'];

                $match = is_null($max)
                    ? ($usia >= $min)
                    : ($usia >= $min && $usia <= $max);

                if ($match) {
                    if ($jk === 'L') {
                        $ageGroupStats[$range['label']]['laki_laki']++;
                    } elseif ($jk === 'P') {
                        $ageGroupStats[$range['label']]['perempuan']++;
                    }

                    $ageGroupStats[$range['label']]['jumlah'] =
                        $ageGroupStats[$range['label']]['laki_laki'] +
                        $ageGroupStats[$range['label']]['perempuan'];

                    break;
                }
            }
        }

        // Hitung sex rasio per kelompok umur
        foreach ($ageGroupStats as &$group) {
            $group['sex_rasio'] = $group['perempuan'] > 0
                ? (int) round(($group['laki_laki'] / $group['perempuan']) * 100)
                : 0;
        }
        unset($group);

        // Grand total
        $ageGrandTotalLakiLaki = array_sum(array_column($ageGroupStats, 'laki_laki'));
        $ageGrandTotalPerempuan = array_sum(array_column($ageGroupStats, 'perempuan'));
        $ageGrandTotalJumlah = array_sum(array_column($ageGroupStats, 'jumlah'));
        $ageGrandTotalSexRasio = $ageGrandTotalPerempuan > 0
            ? (int) round(($ageGrandTotalLakiLaki / $ageGrandTotalPerempuan) * 100)
            : 0;

        // ==========================
        // 8) PEKERJAAN / PROFESI
        // Dinamis berdasarkan isi kolom pekerjaan
        // ==========================
        $jobRows = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereIn('jenis_kelamin', ['L', 'P'])
            ->select(
                DB::raw("
                    CASE
                        WHEN pekerjaan IS NULL
                             OR TRIM(pekerjaan) = ''
                             OR LOWER(TRIM(pekerjaan)) = 'null'
                             OR TRIM(pekerjaan) = '-'
                        THEN 'Belum Bekerja'
                        ELSE TRIM(pekerjaan)
                    END as pekerjaan_group
                "),
                'jenis_kelamin',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('pekerjaan_group', 'jenis_kelamin')
            ->orderBy('pekerjaan_group')
            ->get();

        $jobStats = [];

        foreach ($jobRows as $row) {
            $pekerjaan = $row->pekerjaan_group;

            if (!isset($jobStats[$pekerjaan])) {
                $jobStats[$pekerjaan] = [
                    'pekerjaan' => $pekerjaan,
                    'laki_laki' => 0,
                    'perempuan' => 0,
                    'jumlah'    => 0,
                ];
            }

            if ($row->jenis_kelamin === 'L') {
                $jobStats[$pekerjaan]['laki_laki'] = (int) $row->total;
            } elseif ($row->jenis_kelamin === 'P') {
                $jobStats[$pekerjaan]['perempuan'] = (int) $row->total;
            }

            $jobStats[$pekerjaan]['jumlah'] =
                $jobStats[$pekerjaan]['laki_laki'] +
                $jobStats[$pekerjaan]['perempuan'];
        }

        // Pastikan Belum Bekerja tetap ada walaupun datanya belum ada
        if (!isset($jobStats['Belum Bekerja'])) {
            $jobStats['Belum Bekerja'] = [
                'pekerjaan' => 'Belum Bekerja',
                'laki_laki' => 0,
                'perempuan' => 0,
                'jumlah'    => 0,
            ];
        }

        // Urutkan: semua pekerjaan alfabetis, Belum Bekerja di paling bawah
        $jobStatsCollection = collect($jobStats)
            ->sortBy(function ($item, $key) {
                return $key === 'Belum Bekerja' ? 'zzzzzz_belum_bekerja' : strtolower($key);
            })
            ->values();

        $jobTotals = [
            'laki_laki' => $jobStatsCollection->sum('laki_laki'),
            'perempuan' => $jobStatsCollection->sum('perempuan'),
            'jumlah'    => $jobStatsCollection->sum('jumlah'),
        ];

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
            'age_groups' => [
                'rows' => array_values($ageGroupStats),
                'totals' => [
                    'laki_laki' => $ageGrandTotalLakiLaki,
                    'perempuan' => $ageGrandTotalPerempuan,
                    'jumlah'    => $ageGrandTotalJumlah,
                    'sex_rasio' => $ageGrandTotalSexRasio,
                ],
            ],
            'job_groups' => [
                'rows' => $jobStatsCollection,
                'totals' => $jobTotals,
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