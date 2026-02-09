<?php

namespace App\Http\Controllers;

use App\Models\Penduduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                  ->orWhere('alamat', 'like', "%{$q}%");
            });
        }

        if ($dusun !== '') $query->where('nama_dusun', $dusun);
        if ($rt !== '') $query->where('rt', $this->normalizeRtRw($rt));
        if ($rw !== '') $query->where('rw', $this->normalizeRtRw($rw));

        $penduduks = $query
            ->orderBy('nama_dusun')
            ->orderBy('rw')
            ->orderBy('rt')
            ->orderBy('nama_kepala_keluarga')
            ->paginate($perPage)
            ->withQueryString();

        $dusunOptions = Penduduk::query()
            ->select('nama_dusun')
            ->whereNotNull('nama_dusun')
            ->where('nama_dusun', '!=', '')
            ->distinct()
            ->orderBy('nama_dusun')
            ->pluck('nama_dusun');

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
                  ->orWhere('alamat', 'like', "%{$q}%");
            });
        }
        if ($dusun !== '') $query->where('nama_dusun', $dusun);
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
                'kode_keluarga',
                'nama_kepala_keluarga',
                'alamat',
                'rt',
                'rw',
                'nama_dusun',
                'bulan',
                'tahun',
                'nama_pengisi',
                'pekerjaan',
                'jabatan',
                'sumber_data'
            ]);

            $query->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->kode_keluarga,
                        $r->nama_kepala_keluarga,
                        $r->alamat,
                        $r->rt,
                        $r->rw,
                        $r->nama_dusun,
                        $r->bulan,
                        $r->tahun,
                        $r->nama_pengisi,
                        $r->pekerjaan,
                        $r->jabatan,
                        $r->sumber_data,
                    ]);
                }
            });

            fclose($out);
        }, $filename, $headers);
    }


    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $file = $request->file('file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Gagal membaca file CSV.');
        }

        // Ambil header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'CSV kosong atau format tidak valid.');
        }

        // Normalisasi header -> lower snake-ish
        $header = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $header);

        $required = [
            'kode_keluarga',
            'nama_kepala_keluarga',
            'alamat',
            'rt',
            'rw',
            'nama_dusun',
            'bulan',
            'tahun',
            'nama_pengisi',
            'pekerjaan',
            'jabatan',
            'sumber_data',
        ];

        foreach ($required as $req) {
            if (!in_array($req, $header, true)) {
                fclose($handle);
                return back()->with('error', "Header CSV wajib memuat kolom: {$req}");
            }
        }

        $idx = array_flip($header);

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < count($header)) {
                    $skipped++;
                    continue;
                }

                $data = [
                    'kode_keluarga' => trim((string) $row[$idx['kode_keluarga']]),
                    'nama_kepala_keluarga' => trim((string) $row[$idx['nama_kepala_keluarga']]),
                    'alamat' => trim((string) $row[$idx['alamat']]),
                    'rt' => $this->normalizeRtRw($row[$idx['rt']] ?? ''),
                    'rw' => $this->normalizeRtRw($row[$idx['rw']] ?? ''),
                    'nama_dusun' => trim((string) ($row[$idx['nama_dusun']] ?? '')),
                    'bulan' => (int) trim((string) ($row[$idx['bulan']] ?? 0)),
                    'tahun' => (int) trim((string) ($row[$idx['tahun']] ?? 0)),
                    'nama_pengisi' => trim((string) ($row[$idx['nama_pengisi']] ?? '')),
                    'pekerjaan' => trim((string) ($row[$idx['pekerjaan']] ?? '')),
                    'jabatan' => trim((string) ($row[$idx['jabatan']] ?? '')),
                    'sumber_data' => trim((string) ($row[$idx['sumber_data']] ?? '')),
                ];

                // Validasi minimal per baris
                if ($data['kode_keluarga'] === '' || $data['nama_kepala_keluarga'] === '' || $data['alamat'] === '') {
                    $skipped++;
                    continue;
                }
                if ($data['bulan'] < 1 || $data['bulan'] > 12) {
                    $skipped++;
                    continue;
                }
                if ($data['tahun'] < 1900 || $data['tahun'] > 2100) {
                    $skipped++;
                    continue;
                }
                if ($data['rt'] === '' || $data['rw'] === '') {
                    $skipped++;
                    continue;
                }

                $existing = Penduduk::query()->where('kode_keluarga', $data['kode_keluarga'])->first();

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
            fclose($handle);
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        fclose($handle);

        return back()->with('success', "Import selesai. Insert: {$inserted}, Update: {$updated}, Skip: {$skipped}");
    }

    private function normalizeRtRw($value): string
    {
        $v = trim((string) $value);
        // kalau sudah "001"
        if (preg_match('/^\d{3}$/', $v)) return $v;

        // kalau "1" atau "01"
        $n = (int) preg_replace('/\D+/', '', $v);
        if ($n <= 0) return '';

        return str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
