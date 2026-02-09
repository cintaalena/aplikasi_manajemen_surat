<?php

namespace App\Http\Controllers;

use App\Models\Penduduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Shuchkin\SimpleXLSX;

class PendudukController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dusun = trim((string) $request->query('dusun', ''));
        $rt = trim((string) $request->query('rt', ''));
        $rw = trim((string) $request->query('rw', ''));
        $perPage = (int) $request->query('perPage', 20);
        if (!in_array($perPage, [10, 20, 30, 50], true)) $perPage = 20;

        $query = Penduduk::query();

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('kode_keluarga', 'like', "%{$q}%")
                  ->orWhere('nama_kepala_keluarga', 'like', "%{$q}%")
                  ->orWhere('nama', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('alamat', 'like', "%{$q}%");
            });
        }

        if ($dusun !== '') $query->where('dusun', $dusun);
        if ($rt !== '') $query->where('rt', $this->normalizeRtRw($rt));
        if ($rw !== '') $query->where('rw', $this->normalizeRtRw($rw));

        $penduduks = $query
            ->orderBy('dusun')
            ->orderBy('rw')
            ->orderBy('rt')
            ->orderBy('kode_keluarga')
            ->orderBy('no_urut')
            ->paginate($perPage)
            ->withQueryString();

        $dusunOptions = Penduduk::query()
            ->select('dusun')
            ->whereNotNull('dusun')
            ->where('dusun', '!=', '')
            ->distinct()
            ->orderBy('dusun')
            ->pluck('dusun');

        return Inertia::render('Penduduk/Index', [
            'penduduks' => $penduduks,
            'filters' => [
                'q' => $q,
                'dusun' => $dusun,
                'rt' => $rt,
                'rw' => $rw,
                'perPage' => $perPage,
            ],
            'dusunOptions' => $dusunOptions,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $q = trim((string) $request->query('q', ''));
        $dusun = trim((string) $request->query('dusun', ''));
        $rt = trim((string) $request->query('rt', ''));
        $rw = trim((string) $request->query('rw', ''));

        $query = Penduduk::query();

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('kode_keluarga', 'like', "%{$q}%")
                  ->orWhere('nama_kepala_keluarga', 'like', "%{$q}%")
                  ->orWhere('nama', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('alamat', 'like', "%{$q}%");
            });
        }
        if ($dusun !== '') $query->where('dusun', $dusun);
        if ($rt !== '') $query->where('rt', $this->normalizeRtRw($rt));
        if ($rw !== '') $query->where('rw', $this->normalizeRtRw($rw));

        $filename = 'penduduk-fatubesi-' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'RT', 'RW', 'Dusun', 'Alamat',
                'Kode Keluarga', 'Nama Kepala Keluarga',
                'No.', 'NIK', 'Nama', 'Jenis Kelamin',
                'Hubungan', 'Tempat Lahir', 'Tanggal Lahir', 'Usia',
                'Status', 'Agama', 'Gol. Darah',
                'Kewarganegaraan', 'Etnis/Suku',
                'Pendidikan', 'Pekerjaan'
            ]);

            $query->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->rt,
                        $r->rw,
                        $r->dusun,
                        $r->alamat,
                        $r->kode_keluarga,
                        $r->nama_kepala_keluarga,
                        $r->no_urut,
                        $r->nik,
                        $r->nama,
                        $r->jenis_kelamin,
                        $r->hubungan,
                        $r->tempat_lahir,
                        $r->tanggal_lahir ? $r->tanggal_lahir->format('d-m-Y') : '',
                        $r->usia,
                        $r->status_perkawinan,
                        $r->agama,
                        $r->golongan_darah,
                        $r->kewarganegaraan,
                        $r->etnis,
                        $r->pendidikan,
                        $r->pekerjaan,
                    ]);
                }
            });

            fclose($out);
        }, $filename, $headers);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'], // 10MB
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = [];

        // Read file based on extension
        if (in_array($extension, ['xlsx', 'xls'])) {
            // Read Excel using SimpleXLSX
            $xlsx = SimpleXLSX::parse($path);
            if (!$xlsx) {
                return back()->with('error', 'Gagal membaca file Excel: ' . SimpleXLSX::parseError());
            }
            $rows = $xlsx->rows();
        } else {
            // Read CSV
            $handle = fopen($path, 'r');
            if (!$handle) {
                return back()->with('error', 'Gagal membaca file CSV.');
            }
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return back()->with('error', 'File kosong atau format tidak valid.');
        }

        // Get header and normalize
        $header = array_shift($rows);
        $headerMap = $this->buildHeaderMap($header);

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (count($row) < count($header)) {
                    $skipped++;
                    continue;
                }

                $data = $this->mapRowToData($row, $header, $headerMap);

                // Validasi minimal
                if (empty($data['kode_keluarga']) || empty($data['nama'])) {
                    $skipped++;
                    continue;
                }

                // Check for existing by NIK or kode_keluarga + nama
                $existing = null;
                if (!empty($data['nik'])) {
                    $existing = Penduduk::where('nik', $data['nik'])->first();
                }

                if (!$existing && !empty($data['kode_keluarga']) && !empty($data['nama'])) {
                    $existing = Penduduk::where('kode_keluarga', $data['kode_keluarga'])
                        ->where('nama', $data['nama'])
                        ->first();
                }

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    Penduduk::create($data);
                    $inserted++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        return back()->with('success', "Import selesai! Berhasil insert: {$inserted}, update: {$updated}, skip: {$skipped}");
    }

    /**
     * Build header mapping from various possible column names
     */
    private function buildHeaderMap(array $header): array
    {
        $map = [];

        // Header alias mapping
        $aliases = [
            'no_urut' => ['no', 'no.', 'nomor', 'no urut'],
            'kode_keluarga' => ['kode keluarga', 'no kk', 'nomor kk', 'kk'],
            'nama_kepala_keluarga' => ['kepala keluarga', 'nama kk', 'nama kepala keluarga'],
            'nama' => ['nama', 'nama anggota', 'nama anggota keluarga'],
            'jenis_kelamin' => ['jk', 'jenis kelamin', 'kelamin'],
            'tempat_lahir' => ['tempat lahir', 'tmp lahir'],
            'tanggal_lahir' => ['tanggal lahir', 'tgl lahir', 'tgl. lahir'],
            'status_perkawinan' => ['status', 'status perkawinan', 'status kawin'],
            'golongan_darah' => ['gdarah', 'gol darah', 'golongan darah', 'gol. darah'],
            'etnis' => ['etnis', 'suku', 'etnis/suku'],
            'kewarganegaraan' => ['warga negara', 'kewarganegaraan', 'wn'],
            'rt' => ['rt'],
            'rw' => ['rw'],
            'dusun' => ['dusun', 'nama dusun'],
            'alamat' => ['alamat'],
            'nik' => ['nik'],
            'hubungan' => ['hubungan', 'hub', 'hub. keluarga'],
            'usia' => ['usia', 'umur'],
            'agama' => ['agama'],
            'pendidikan' => ['pendidikan', 'pend', 'pend.'],
            'pekerjaan' => ['pekerjaan', 'pek', 'pek.'],
        ];

        foreach ($header as $index => $col) {
            $normalized = strtolower(trim($col));
            
            foreach ($aliases as $field => $possibleNames) {
                if (in_array($normalized, $possibleNames)) {
                    $map[$index] = $field;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Map row data to database fields
     */
    private function mapRowToData(array $row, array $header, array $headerMap): array
    {
        $data = [
            'kode_keluarga' => '',
            'nama_kepala_keluarga' => '',
            'alamat' => '',
            'rt' => '',
            'rw' => '',
            'dusun' => '',
            'no_urut' => null,
            'nik' => null,
            'nama' => '',
            'jenis_kelamin' => null,
            'hubungan' => null,
            'tempat_lahir' => null,
            'tanggal_lahir' => null,
            'usia' => null,
            'status_perkawinan' => null,
            'agama' => null,
            'golongan_darah' => null,
            'kewarganegaraan' => 'WNI',
            'etnis' => null,
            'pendidikan' => null,
            'pekerjaan' => null,
        ];

        foreach ($headerMap as $index => $field) {
            if (!isset($row[$index])) continue;

            $value = trim((string) $row[$index]);
            if ($value === '') continue;

            switch ($field) {
                case 'rt':
                case 'rw':
                    $data[$field] = $this->normalizeRtRw($value);
                    break;

                case 'jenis_kelamin':
                    $data[$field] = $this->normalizeJenisKelamin($value);
                    break;

                case 'tanggal_lahir':
                    $data[$field] = $this->parseTanggalLahir($value);
                    break;

                case 'no_urut':
                case 'usia':
                    $data[$field] = (int) $value ?: null;
                    break;

                default:
                    $data[$field] = $value;
            }
        }

        return $data;
    }

    /**
     * Normalize RT/RW to 3-digit format
     */
    private function normalizeRtRw($value): string
    {
        $v = trim((string) $value);
        if (preg_match('/^\d{3}$/', $v)) return $v;

        $n = (int) preg_replace('/\D+/', '', $v);
        if ($n <= 0) return '';

        return str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Normalize jenis kelamin
     */
    private function normalizeJenisKelamin($value): ?string
    {
        $v = strtoupper(trim((string) $value));
        if (in_array($v, ['L', 'LAKI-LAKI', 'LAKI', 'MALE', 'M'])) return 'L';
        if (in_array($v, ['P', 'PEREMPUAN', 'FEMALE', 'F'])) return 'P';
        return null;
    }

    /**
     * Parse tanggal lahir from various formats
     */
    private function parseTanggalLahir($value): ?string
    {
        if (!$value) return null;

        $formats = [
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'Y-m-d',
            'd-M-Y',
            'd M Y',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
