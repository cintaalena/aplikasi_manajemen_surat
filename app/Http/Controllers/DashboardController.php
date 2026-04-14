<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exports\DashboardDataExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Home/Index');
    }

    public function metrics(Request $request)
    {
        return response()->json($this->buildMetricsData());
    }

    /**
     * Detail surat untuk bulan & tahun tertentu.
     * GET /dashboard/letters-by-month?year=2026&month=4
     */
    public function lettersByMonth(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        // Clamp
        $year  = max(2000, min($year, 2100));
        $month = max(1, min($month, 12));

        $timeCol   = $this->resolveLetterTimeColumn();
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // Total bulan ini
        $total = DB::table('letters')
            ->whereBetween($timeCol, [$startDate, $endDate])
            ->count();

        // Per template
        $byTemplate = DB::table('letters')
            ->select('template_slug', DB::raw('COUNT(*) as total'))
            ->whereBetween($timeCol, [$startDate, $endDate])
            ->groupBy('template_slug')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'template_slug' => $r->template_slug,
                'label'         => $this->labelTemplate($r->template_slug),
                'total'         => (int) $r->total,
            ])
            ->values()
            ->toArray();

        // Per minggu dalam bulan — inklusif daftar surat tiap minggu
        $weeks = [];
        $cursor = $startDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $weekNum = 1;
        while ($cursor->lte($endDate)) {
            $weekStart = $cursor->copy()->max($startDate);
            $weekEnd   = $cursor->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->min($endDate);

            // Ambil daftar surat di minggu ini (urut by time asc)
            $letters = DB::table('letters')
                ->whereBetween($timeCol, [$weekStart, $weekEnd])
                ->orderBy($timeCol)
                ->get(['id', 'template_slug', 'title', 'no_surat', $timeCol])
                ->map(function ($row) use ($timeCol) {
                    return [
                        'id'            => $row->id,
                        'no_surat'      => $row->no_surat ?? '-',
                        'title'         => $row->title ?? '-',
                        'template_slug' => $row->template_slug,
                        'label'         => $this->labelTemplate($row->template_slug),
                        'tanggal'       => $row->{$timeCol}
                            ? \Carbon\Carbon::parse($row->{$timeCol})->translatedFormat('d M Y')
                            : '-',
                    ];
                })
                ->values()
                ->toArray();

            // Rekap per template dalam minggu ini
            $templateSummary = collect($letters)
                ->groupBy('template_slug')
                ->map(fn($items, $slug) => [
                    'template_slug' => $slug,
                    'label'         => $this->labelTemplate($slug),
                    'total'         => count($items),
                ])
                ->values()
                ->toArray();

            $weeks[] = [
                'week'             => $weekNum,
                'start'            => $weekStart->toDateString(),
                'end'              => $weekEnd->toDateString(),
                'total'            => count($letters),
                'letters'          => $letters,
                'template_summary' => $templateSummary,
            ];

            $cursor->addWeek();
            $weekNum++;
        }

        return response()->json([
            'year'        => $year,
            'month'       => $month,
            'month_label' => \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y'),
            'total'       => $total,
            'by_template' => $byTemplate,
            'by_week'     => $weeks,
        ]);
    }

    public function exportExcel()
    {
        $fileName = 'dashboard-kelurahan-fatubesi-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new DashboardDataExport($this->buildMetricsData()),
            $fileName
        );
    }

    private function buildMetricsData(): array
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

        // Ringkasan 12 bulan terakhir (inklusif bulan ini)
        $monthly12 = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i)->startOfMonth();
            $monthly12[] = [
                'year'        => (int) $m->year,
                'month'       => (int) $m->month,
                'month_label' => $m->translatedFormat('M Y'),
                'total'       => (int) DB::table('letters')
                    ->whereBetween($timeCol, [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                    ->count(),
            ];
        }

        // Tambahan untuk export sheet surat
        $totalLetters = DB::table('letters')->count();

        $lettersByTemplate = DB::table('letters')
            ->select('template_slug', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('template_slug')
            ->orderByDesc('jumlah')
            ->get()
            ->map(function ($row) {
                return [
                    'template_slug' => $row->template_slug,
                    'label'         => $this->labelTemplate($row->template_slug),
                    'jumlah'        => (int) $row->jumlah,
                ];
            })
            ->values()
            ->toArray();

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
            })
            ->values()
            ->toArray();

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

        // Untuk export sheet penduduk/agama
        $religionRows = [
            ['agama' => 'Kristen', 'jumlah' => (int) $totalKristen],
            ['agama' => 'Islam', 'jumlah' => (int) $totalIslam],
            ['agama' => 'Katholik', 'jumlah' => (int) $totalKatholik],
            ['agama' => 'Hindu', 'jumlah' => (int) $totalHindu],
            ['agama' => 'Buddha', 'jumlah' => (int) $totalBuddha],
            ['agama' => 'Konghucu', 'jumlah' => (int) $totalKonghucu],
        ];

        // ==========================
        // 5) GENDER SEMUA JIWA PER RT/RW
        // ==========================
        $genderPerRtRw = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereNotNull('rt')->where('rt', '!=', '')
            ->whereNotNull('rw')->where('rw', '!=', '')
            ->select(
                'rw',
                'rt',
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
                'rt_rw'     => trim(($row->rt ?? '-') . '/' . ($row->rw ?? '-')),
                'laki_laki' => (int) $row->laki_laki,
                'perempuan' => (int) $row->perempuan,
                'jumlah'    => (int) $row->total,
                'total'     => (int) $row->total,
            ])
            ->values();

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
                'jumlah'    => (int) $row->total,
                'total'     => (int) $row->total,
            ])
            ->values();

        $genderTotals = [
            'laki_laki' => $genderPerRtRw->sum('laki_laki'),
            'perempuan' => $genderPerRtRw->sum('perempuan'),
            'jumlah'    => $genderPerRtRw->sum('jumlah'),
        ];

        // ==========================
        // 6) KEPALA KELUARGA PER RT/RW
        // ==========================
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
                'rt_rw'        => trim(($row->rt ?? '-') . '/' . ($row->rw ?? '-')),
                'kk_laki_laki' => (int) $row->kk_laki_laki,
                'kk_perempuan' => (int) $row->kk_perempuan,
                'jumlah'       => (int) $row->total_kk,
                'total_kk'     => (int) $row->total_kk,
            ])
            ->values();

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
                'jumlah'       => (int) $row->total_kk,
                'total_kk'     => (int) $row->total_kk,
            ])
            ->values();

        $kkGrandTotal = DB::table(DB::raw("({$kepalaKeluargaRtRw->toSql()}) as kepala_keluarga_grand"))
            ->mergeBindings($kepalaKeluargaRtRw)
            ->select(
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as kk_laki_laki"),
                DB::raw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as kk_perempuan"),
                DB::raw('COUNT(*) as total_kk')
            )
            ->first();

        $kkTotals = [
            'laki_laki' => (int) ($kkGrandTotal->kk_laki_laki ?? 0),
            'perempuan' => (int) ($kkGrandTotal->kk_perempuan ?? 0),
            'jumlah'    => (int) ($kkGrandTotal->total_kk ?? 0),
        ];

        // ==========================
        // 7) KELOMPOK UMUR
        // ==========================
        $ageRanges = [
            ['label' => '00-05', 'min' => 0, 'max' => 5],
            ['label' => '06-10', 'min' => 6, 'max' => 10],
            ['label' => '11-15', 'min' => 11, 'max' => 15],
            ['label' => '16-20', 'min' => 16, 'max' => 20],
            ['label' => '21-25', 'min' => 21, 'max' => 25],
            ['label' => '26-30', 'min' => 26, 'max' => 30],
            ['label' => '31-35', 'min' => 31, 'max' => 35],
            ['label' => '36-40', 'min' => 36, 'max' => 40],
            ['label' => '41-45', 'min' => 41, 'max' => 45],
            ['label' => '46-50', 'min' => 46, 'max' => 50],
            ['label' => '51-55', 'min' => 51, 'max' => 55],
            ['label' => '56-60', 'min' => 56, 'max' => 60],
            ['label' => '61 ke atas', 'min' => 61, 'max' => null],
        ];

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

        foreach ($ageGroupStats as &$group) {
            $group['sex_rasio'] = $group['perempuan'] > 0
                ? (int) round(($group['laki_laki'] / $group['perempuan']) * 100)
                : 0;
        }
        unset($group);

        $ageGrandTotalLakiLaki = array_sum(array_column($ageGroupStats, 'laki_laki'));
        $ageGrandTotalPerempuan = array_sum(array_column($ageGroupStats, 'perempuan'));
        $ageGrandTotalJumlah = array_sum(array_column($ageGroupStats, 'jumlah'));
        $ageGrandTotalSexRasio = $ageGrandTotalPerempuan > 0
            ? (int) round(($ageGrandTotalLakiLaki / $ageGrandTotalPerempuan) * 100)
            : 0;

        // ==========================
        // 8) PEKERJAAN / PROFESI
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

        if (!isset($jobStats['Belum Bekerja'])) {
            $jobStats['Belum Bekerja'] = [
                'pekerjaan' => 'Belum Bekerja',
                'laki_laki' => 0,
                'perempuan' => 0,
                'jumlah'    => 0,
            ];
        }

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

        // ==========================
        // 9) PENDIDIKAN TERAKHIR
        // ==========================
        $educationCategories = [
            'Belum Sekolah',
            'PAUD',
            'TK',
            'SD',
            'SMP',
            'SMU/SMK/MA',
            'DI / DII / DIII',
            'S1',
            'S2',
            'S3',
            'Buta Huruf',
        ];

        $educationStats = [];
        foreach ($educationCategories as $category) {
            $educationStats[$category] = [
                'pendidikan' => $category,
                'laki_laki'  => 0,
                'perempuan'  => 0,
                'jumlah'     => 0,
            ];
        }

        $educationRows = DB::table('penduduks')
            ->whereNull('deleted_at')
            ->whereIn('jenis_kelamin', ['L', 'P'])
            ->select('pendidikan', 'jenis_kelamin')
            ->get();

        foreach ($educationRows as $row) {
            $kategori = $this->mapEducationGroup($row->pendidikan);

            if (!isset($educationStats[$kategori])) {
                continue;
            }

            if ($row->jenis_kelamin === 'L') {
                $educationStats[$kategori]['laki_laki']++;
            } elseif ($row->jenis_kelamin === 'P') {
                $educationStats[$kategori]['perempuan']++;
            }

            $educationStats[$kategori]['jumlah'] =
                $educationStats[$kategori]['laki_laki'] +
                $educationStats[$kategori]['perempuan'];
        }

        $educationStatsCollection = collect($educationStats)->values();

        $educationTotals = [
            'laki_laki' => $educationStatsCollection->sum('laki_laki'),
            'perempuan' => $educationStatsCollection->sum('perempuan'),
            'jumlah'    => $educationStatsCollection->sum('jumlah'),
        ];

        return [
            'letters' => [
                'today' => $lettersToday,
                'week'  => $lettersWeek,
                'month' => $lettersMonth,
                'year'  => $lettersYear,
            ],

            'letters_monthly_12' => $monthly12,

            // dipakai untuk export
            'total_letters' => $totalLetters,
            'letters_by_template' => $lettersByTemplate,

            'top_templates_30d' => $topTemplates,

            'population' => [
                'jumlah_rt'              => $jumlahRt,
                'jumlah_rw'              => $jumlahRw,
                'total_laki_laki'        => $jumlahLakiLaki,
                'total_perempuan'        => $jumlahPerempuan,
                'jumlah_jiwa'            => $jumlahJiwa,
                'jumlah_kepala_keluarga' => $jumlahKepalaKeluarga,
                'total_kk'               => $totalKk,

                // tambahan untuk export
                'total'                  => $jumlahJiwa,
                'laki_laki'              => $jumlahLakiLaki,
                'perempuan'              => $jumlahPerempuan,
                'kepala_keluarga'        => $jumlahKepalaKeluarga,
            ],

            'agama' => [
                'kristen'  => $totalKristen,
                'islam'    => $totalIslam,
                'katholik' => $totalKatholik,
                'hindu'    => $totalHindu,
                'buddha'   => $totalBuddha,
                'konghucu' => $totalKonghucu,
            ],

            // tambahan untuk export
            'religion_groups' => [
                'rows' => $religionRows,
                'totals' => [
                    'jumlah' => array_sum(array_column($religionRows, 'jumlah')),
                ],
            ],

            'gender_per_rt_rw' => $genderPerRtRw,
            'subtotal_per_rw'  => $subtotalPerRw,

            // tambahan untuk export
            'gender_groups' => [
                'rows' => $genderPerRtRw,
                'totals' => $genderTotals,
            ],

            'kk_per_rt_rw'       => $kkPerRtRw,
            'kk_subtotal_per_rw' => $kkSubtotalPerRw,
            'kk_grand_total'     => [
                'kk_laki_laki' => (int) ($kkGrandTotal->kk_laki_laki ?? 0),
                'kk_perempuan' => (int) ($kkGrandTotal->kk_perempuan ?? 0),
                'total_kk'     => (int) ($kkGrandTotal->total_kk ?? 0),
            ],

            // tambahan untuk export
            'kk_groups' => [
                'rows' => $kkPerRtRw,
                'totals' => $kkTotals,
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
                'rows' => $jobStatsCollection->values()->toArray(),
                'totals' => $jobTotals,
            ],

            'education_groups' => [
                'rows' => $educationStatsCollection->values()->toArray(),
                'totals' => $educationTotals,
            ],

            'meta' => [
                'time_column'  => $timeCol,
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    private function resolveLetterTimeColumn(): string
    {
        try {
            if (Schema::hasColumn('letters', 'printed_at')) {
                return 'printed_at';
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return 'created_at';
    }

    private function labelTemplate(?string $slug): string
    {
        if (!$slug) {
            return '-';
        }

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

    private function mapEducationGroup(?string $pendidikan): string
    {
        $value = strtolower(trim((string) $pendidikan));
        $value = preg_replace('/\s+/', ' ', $value);

        if ($value === '' || $value === 'null' || $value === '-') {
            return 'Belum Sekolah';
        }

        if (str_contains($value, 'buta huruf')) {
            return 'Buta Huruf';
        }

        if (
            str_contains($value, 'tidak pernah sekolah') ||
            str_contains($value, 'belum masuk tk')
        ) {
            return 'Belum Sekolah';
        }

        if (preg_match('/\bs[\-\s]?3\b|\bstrata[\-\s]?3\b/i', $value)) {
            return 'S3';
        }

        if (preg_match('/\bs[\-\s]?2\b|\bstrata[\-\s]?2\b/i', $value)) {
            return 'S2';
        }

        if (preg_match('/\bd[\-\s]?4\b|\bs[\-\s]?1\b|\bstrata[\-\s]?1\b/i', $value)) {
            return 'S1';
        }

        if (preg_match('/\bd[\-\s]?(1|2|3)\b/i', $value)) {
            return 'DI / DII / DIII';
        }

        if (str_contains($value, 'smp') || str_contains($value, 'sltp')) {
            return 'SMP';
        }

        if (
            str_contains($value, 'slta') ||
            str_contains($value, 'sma') ||
            str_contains($value, 'smk') ||
            preg_match('/\bma\b/i', $value)
        ) {
            return 'SMU/SMK/MA';
        }

        if (preg_match('/\bsd\b/i', $value)) {
            return 'SD';
        }

        if (preg_match('/\btk\b|taman kanak/i', $value)) {
            return 'TK';
        }

        if (str_contains($value, 'paud') || str_contains($value, 'kelompok bermain')) {
            return 'PAUD';
        }

        return 'Belum Sekolah';
    }
}