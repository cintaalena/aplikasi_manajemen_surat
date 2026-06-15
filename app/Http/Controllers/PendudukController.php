<?php

namespace App\Http\Controllers;

use App\Models\Penduduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;
use Carbon\Carbon;

class PendudukController extends Controller
{
    public function searchByName(Request $request)
{
    try {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $penduduks = Penduduk::query()
            ->select([
                'id',
                'nik',
                'nama',
                'jenis_kelamin',
                'tempat_lahir',
                'tanggal_lahir',
                'agama',
                'pekerjaan',
                'alamat',
                'rt',
                'rw',
                'dusun',
                'kode_keluarga',
                'nama_kepala_keluarga',
                'status_perkawinan',
                'kewarganegaraan',
            ])
            ->where('status_kehidupan', 'Hidup')
            ->where('nama', 'like', '%' . $q . '%')
            ->orderBy('nama', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nik' => $p->nik,
                    'nama' => $p->nama,
                    'jenis_kelamin' => $p->jenis_kelamin,
                    'tempat_lahir' => $p->tempat_lahir,
                    'tanggal_lahir' => $p->tanggal_lahir
                        ? (is_string($p->tanggal_lahir)
                            ? $p->tanggal_lahir
                            : $p->tanggal_lahir->format('Y-m-d'))
                        : '',
                    'agama' => $p->agama,
                    'pekerjaan' => $p->pekerjaan,
                    'alamat' => $p->alamat,
                    'rt' => $p->rt,
                    'rw' => $p->rw,
                    'dusun' => $p->dusun,
                    'kode_keluarga' => $p->kode_keluarga,
                    'nama_kepala_keluarga' => $p->nama_kepala_keluarga,
                    'status_perkawinan' => $p->status_perkawinan,
                    'kewarganegaraan' => $p->kewarganegaraan,
                ];
            })
            ->values();

        return response()->json($penduduks);
    } catch (\Throwable $e) {
        \Log::error('searchByName penduduk gagal: ' . $e->getMessage());

        return response()->json([
            'message' => 'Terjadi kesalahan saat mencari data penduduk',
        ], 500);
    }
}
    public function create()
    {
        return inertia('Penduduk/Create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
    'is_kepala_keluarga' => ['nullable', 'boolean'],
    'kode_keluarga' => ['nullable', 'string', 'max:32'],
    'kepala_keluarga_kode' => ['nullable', 'string', 'max:32'],

    'rt' => ['nullable', 'string', 'max:3'],
    'rw' => ['nullable', 'string', 'max:3'],
    'dusun' => ['nullable', 'string', 'max:100'],
    'alamat' => ['nullable', 'string'],

    'nik' => ['required', 'string', 'max:20', 'unique:penduduks,nik'],
    'nama' => ['required', 'string', 'max:150'],
    'jenis_kelamin' => ['required', 'in:L,P'],
    'hubungan' => ['nullable', 'string', 'max:50'],
    'tempat_lahir' => ['nullable', 'string', 'max:100'],
    'tanggal_lahir' => ['nullable', 'date'],
    'status_perkawinan' => ['nullable', 'string', 'max:50'],
    'agama' => ['nullable', 'string', 'max:30'],
    'golongan_darah' => ['nullable', 'string', 'max:3'],
    'kewarganegaraan' => ['nullable', 'string', 'max:50'],
    'etnis' => ['nullable', 'string', 'max:50'],
    'pendidikan' => ['nullable', 'string', 'max:100'],
    'pekerjaan' => ['nullable', 'string', 'max:100'],
], [
    'nik.required' => 'NIK wajib diisi.',
    'nik.unique' => 'NIK sudah terdaftar.',
    'nama.required' => 'Nama wajib diisi.',
    'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
    'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
]);

    $isKepalaKeluarga = $request->boolean('is_kepala_keluarga');

    if ($isKepalaKeluarga) {
    if (blank($validated['kode_keluarga'] ?? null)) {
        return back()->withErrors([
            'kode_keluarga' => 'No. KK wajib diisi untuk kepala keluarga.',
        ])->withInput();
    }

    if (blank($validated['rt'] ?? null)) {
        return back()->withErrors([
            'rt' => 'RT wajib diisi untuk kepala keluarga.',
        ])->withInput();
    }

    if (blank($validated['rw'] ?? null)) {
        return back()->withErrors([
            'rw' => 'RW wajib diisi untuk kepala keluarga.',
        ])->withInput();
    }

    if (blank($validated['alamat'] ?? null)) {
        return back()->withErrors([
            'alamat' => 'Alamat wajib diisi untuk kepala keluarga.',
        ])->withInput();
    }

    $existing = Penduduk::where('kode_keluarga', $validated['kode_keluarga'])->exists();

    if ($existing) {
        return back()->withErrors([
            'kode_keluarga' => 'No. KK sudah terdaftar. Gunakan No. KK lain.',
        ])->withInput();
    }

    $validated['nama_kepala_keluarga'] = $validated['nama'];
    $validated['hubungan'] = 'Kepala Keluarga';
    $validated['no_urut'] = 1;
    $validated['rt'] = str_pad((string) $validated['rt'], 3, '0', STR_PAD_LEFT);
    $validated['rw'] = str_pad((string) $validated['rw'], 3, '0', STR_PAD_LEFT);
    
    }else {
        if (blank($validated['kepala_keluarga_kode'] ?? null)) {
            return back()->withErrors([
                'kepala_keluarga_kode' => 'Silakan pilih kepala keluarga yang sudah terdaftar.',
            ])->withInput();
        }

        $keluarga = Penduduk::where('kode_keluarga', $validated['kepala_keluarga_kode'])->get();

        if ($keluarga->isEmpty()) {
            return back()->withErrors([
                'kepala_keluarga_kode' => 'Kepala keluarga tidak ditemukan di database.',
            ])->withInput();
        }

        $first = $keluarga->first();

        if (blank($first->kode_keluarga)) {
            return back()->withErrors([
                'kepala_keluarga_kode' => 'Kepala keluarga yang dipilih tidak memiliki No. KK yang valid. Perbaiki data kepala keluarga tersebut terlebih dahulu.',
            ])->withInput();
        }

        $validated['kode_keluarga'] = $first->kode_keluarga;
        $validated['nama_kepala_keluarga'] = $first->nama_kepala_keluarga;
        $validated['alamat'] = $first->alamat;
        $validated['rt'] = $first->rt;
        $validated['rw'] = $first->rw;
        $validated['dusun'] = $first->dusun;
        $validated['no_urut'] = ((int) $keluarga->max('no_urut')) + 1;

        if (($validated['hubungan'] ?? '') === 'Kepala Keluarga') {
            return back()->withErrors([
                'hubungan' => 'Untuk anggota keluarga, hubungan tidak boleh Kepala Keluarga.',
            ])->withInput();
        }
    }

    $validated['usia'] = !empty($validated['tanggal_lahir'])
        ? Carbon::parse($validated['tanggal_lahir'])->age
        : null;

    unset($validated['is_kepala_keluarga'], $validated['kepala_keluarga_kode']);

    Penduduk::create($validated);

    return redirect()
        ->route('penduduk.index')
        ->with('success', 'Data penduduk berhasil ditambahkan.');
}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dusun = trim((string) $request->query('dusun', ''));
        $rt = trim((string) $request->query('rt', ''));
        $rw = trim((string) $request->query('rw', ''));
        $statusKehidupan = trim((string) $request->query('status_kehidupan', ''));
        $perPage = (int) $request->query('perPage', 20);
        if (!in_array($perPage, [10, 20, 30, 50], true)) $perPage = 20;

        $query = Penduduk::query()
    ->select([
        'id',
        'rt',
        'rw',
        'dusun',
        'alamat',
        'kode_keluarga',
        'nama_kepala_keluarga',
        'no_urut',
        'nik',
        'nama',
        'jenis_kelamin',
        'hubungan',
        'tempat_lahir',
        'tanggal_lahir',
        'usia',
        'status_perkawinan',
        'agama',
        'golongan_darah',
        'kewarganegaraan',
        'etnis',
        'pendidikan',
        'pekerjaan',
        'status_kehidupan',
    ]);

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

        if (auth()->user()->role !== 'admin') {
            $query->where('status_kehidupan', '!=', 'Pindah');
            if ($statusKehidupan !== '' && $statusKehidupan !== 'Pindah') {
                $query->where('status_kehidupan', $statusKehidupan);
            }
        } else {
            if ($statusKehidupan !== '') {
                $query->where('status_kehidupan', $statusKehidupan);
            }
        }

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
                'status_kehidupan' => $statusKehidupan,
            ],
            'dusunOptions' => $dusunOptions,
            'importSuccess' => session('success'),
            'importError'   => session('error'),
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

        $query->where('status_kehidupan', '!=', 'Meninggal');

        $filename = 'penduduk-fatubesi-' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

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
                        $r->tanggal_lahir ? (is_string($r->tanggal_lahir) ? $r->tanggal_lahir : $r->tanggal_lahir->format('d-m-Y')) : '',
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

    /**
     * Helper: flash pesan ke session lalu paksa full browser reload ke penduduk.index.
     * Digunakan agar notifikasi terbaca oleh SEMUA role (admin, staff) tanpa masalah timing XHR.
     */
    private function importRedirect(string $key, string $message): \Symfony\Component\HttpFoundation\Response
    {
        session()->flash($key, $message);
        return Inertia::location(route('penduduk.index'));
    }

    public function import(Request $request)
    {
        if ($request->server('CONTENT_LENGTH') !== null && (int)$request->server('CONTENT_LENGTH') > 10485760) {
            return $this->importRedirect('error', '❌ Upload Gagal: Ukuran file melebihi 10MB (server limit)');
        }
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'extensions:csv,txt,xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'File harus dipilih!',
            'file.extensions' => 'Format file harus .csv, .txt, .xlsx, atau .xls',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        if ($validator->fails()) {
            return $this->importRedirect('error', '❌ Upload Gagal: ' . $validator->errors()->first());
        }

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = [];

        try {
            if ($extension === 'xlsx') {
                $xlsx = SimpleXLSX::parse($path);
                if (!$xlsx) {
                    $error = SimpleXLSX::parseError();
                    return $this->importRedirect('error', "❌ Gagal membaca file Excel (.xlsx): {$error}\n\nSolusi: Pastikan file tidak corrupt dan format valid.");
                }
                $rows = $xlsx->rows();
            } elseif ($extension === 'xls') {
                if (!class_exists(SimpleXLS::class)) {
                    return $this->importRedirect('error', "❌ Library pembaca .xls tidak tersedia.\n\nSolusi: Simpan ulang file menjadi .xlsx atau .csv lalu upload ulang.");
                }
                $xls = SimpleXLS::parse($path);
                if (!$xls) {
                    $error = SimpleXLS::parseError();
                    return $this->importRedirect('error', "❌ Gagal membaca file Excel (.xls): {$error}\n\nSolusi: Coba save ulang file sebagai .xlsx atau .csv");
                }
                $rows = $xls->rows();
            } else {
                $handle = fopen($path, 'r');
                if (!$handle) {
                    return $this->importRedirect('error', "❌ Gagal membuka file CSV.\n\nSolusi: Pastikan file CSV tidak sedang dibuka di aplikasi lain.");
                }

                $firstLine = fgets($handle);
                rewind($handle);

                $delimiter = ',';
                if ($firstLine !== false) {
                    $commaCount = substr_count($firstLine, ',');
                    $semicolonCount = substr_count($firstLine, ';');
                    $delimiter = $semicolonCount > $commaCount ? ';' : ',';
                }

                while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rows[] = $line;
                }

                fclose($handle);
            }
        } catch (\Throwable $e) {
            return $this->importRedirect('error', "❌ Error membaca file '{$filename}': {$e->getMessage()}\n\nSolusi: Coba tutup file di Excel/LibreOffice, lalu upload ulang.");
        }

        if (empty($rows)) {
            return $this->importRedirect('error', "❌ File '{$filename}' kosong atau tidak memiliki data.\n\nSolusi: Pastikan file memiliki minimal 1 baris header dan 1 baris data.");
        }

        if (count($rows) < 2) {
            return $this->importRedirect('error', "❌ File hanya memiliki header tanpa data.\n\nData ditemukan: " . count($rows) . " baris\n\nSolusi: Tambahkan minimal 1 baris data setelah header.");
        }

        $header = array_shift($rows);
        $headerMap = $this->buildHeaderMap($header);

        if (empty($headerMap)) {
            $headerList = implode(', ', array_slice($header, 0, 12));
            return $this->importRedirect('error', "❌ Tidak ada kolom yang dikenali dari header file.\n\nHeader ditemukan: {$headerList}...\n\nSolusi: Pastikan file memiliki kolom minimal:\n- 'Nama' / 'Nama Anggota Keluarga'\n- 'Kode Keluarga'\n- 'RT', 'RW', 'Dusun'");
        }

        $requiredMapped = ['nama', 'kode_keluarga'];
        $missingRequired = [];
        $mappedFields = array_values($headerMap);

        foreach ($requiredMapped as $req) {
            if (!in_array($req, $mappedFields, true)) {
                $missingRequired[] = $req;
            }
        }

        if (!empty($missingRequired)) {
            $missing = implode(', ', $missingRequired);
            $headerList = implode(', ', $header);
            return $this->importRedirect('error', "❌ Kolom wajib tidak ditemukan: {$missing}\n\nHeader file Anda: {$headerList}\n\nSolusi: Pastikan kolom 'Nama Anggota Keluarga' dan 'Kode Keluarga' ada di file Excel.");
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 2;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (empty(array_filter($row))) {
                    $skipped++;
                    $rowNumber++;
                    continue;
                }

                if (count($row) < 4) {
                    $skipped++;
                    if (count($errors) < 5) {
                        $errors[] = "Baris {$rowNumber}: Baris terlalu pendek / tidak valid";
                    }
                    $rowNumber++;
                    continue;
                }

                $data = $this->mapRowToData($row, $header, $headerMap);
                if (empty($data['status_kehidupan'])) {
                    $data['status_kehidupan'] = 'Hidup';
                }

                if (empty($data['kode_keluarga'])) {
                    $skipped++;
                    if (count($errors) < 5) {
                        $errors[] = "Baris {$rowNumber}: Kode Keluarga kosong";
                    }
                    $rowNumber++;
                    continue;
                }

                if (empty($data['nama'])) {
                    $skipped++;
                    if (count($errors) < 5) {
                        $errors[] = "Baris {$rowNumber}: Nama kosong";
                    }
                    $rowNumber++;
                    continue;
                }

                $existing = null;
                if (!empty($data['nik'])) {
                    $existing = Penduduk::where('nik', $data['nik'])->first();
                }
                if (!$existing && !empty($data['kode_keluarga']) && !empty($data['nama'])) {
                    $existing = Penduduk::where('kode_keluarga', $data['kode_keluarga'])
                        ->where('nama', $data['nama'])
                        ->first();
                }

                try {
                    if ($existing) {
                        $existing->fill($data);
                        $existing->save();
                        $updated++;
                    } else {
                        Penduduk::create($data);
                        $inserted++;
                    }
                } catch (\Throwable $e) {
                    $skipped++;
                    if (count($errors) < 5) {
                        $errors[] = "Baris {$rowNumber}: Error database - " . $e->getMessage();
                    }
                }

                $rowNumber++;
            }

            DB::commit();

            $successMsg = "✅ Import selesai!\n\n";
            $successMsg .= "📊 Statistik:\n";
            $successMsg .= "• Berhasil INSERT: {$inserted} data baru\n";
            $successMsg .= "• Berhasil UPDATE: {$updated} data existing\n";
            $successMsg .= "• Dilewati: {$skipped} baris\n";
            $successMsg .= "• Total diproses: " . ($inserted + $updated + $skipped) . " baris\n";

            if (!empty($errors)) {
                $successMsg .= "\n⚠️ Peringatan (5 error pertama):\n";
                $successMsg .= implode("\n", $errors);
            }

            return $this->importRedirect('success', $successMsg);

        } catch (\Throwable $e) {
            DB::rollBack();

            $errorMsg = "❌ Import GAGAL pada baris {$rowNumber}\n\n";
            $errorMsg .= "Error: " . $e->getMessage() . "\n\n";
            $errorMsg .= "Solusi:\n";
            $errorMsg .= "1. Cek format data pada baris {$rowNumber}\n";
            $errorMsg .= "2. Pastikan NIK tidak duplikat (jika ada)\n";
            $errorMsg .= "3. Pastikan format tanggal benar (dd-mm-yyyy)\n";
            $errorMsg .= "4. Pastikan jenis kelamin L atau P\n\n";
            $errorMsg .= "Data yang sukses di-import: INSERT {$inserted}, UPDATE {$updated}";

            return $this->importRedirect('error', $errorMsg);
        }
    }

    public function edit(Penduduk $penduduk)
    {
        return Inertia::render('Penduduk/Edit', [
            'penduduk' => $penduduk,
        ]);
    }

    public function update(Request $request, Penduduk $penduduk)
    {
        $validated = $request->validate([
            'kode_keluarga'        => ['nullable', 'string', 'max:32'],
            'nama_kepala_keluarga' => ['nullable', 'string', 'max:150'],
            'rt'                   => ['nullable', 'string', 'max:3'],
            'rw'                   => ['nullable', 'string', 'max:3'],
            'dusun'                => ['nullable', 'string', 'max:100'],
            'alamat'               => ['nullable', 'string'],
            'no_urut'              => ['nullable', 'integer'],
            'nik'                  => ['required', 'string', 'max:20', 'unique:penduduks,nik,' . $penduduk->id],
            'nama'                 => ['required', 'string', 'max:150'],
            'jenis_kelamin'        => ['required', 'in:L,P'],
            'hubungan'             => ['nullable', 'string', 'max:50'],
            'tempat_lahir'         => ['nullable', 'string', 'max:100'],
            'tanggal_lahir'        => ['nullable', 'date'],
            'status_perkawinan'    => ['nullable', 'string', 'max:50'],
            'agama'                => ['nullable', 'string', 'max:30'],
            'golongan_darah'       => ['nullable', 'string', 'max:3'],
            'kewarganegaraan'      => ['nullable', 'string', 'max:50'],
            'etnis'                => ['nullable', 'string', 'max:50'],
            'pendidikan'           => ['nullable', 'string', 'max:100'],
            'pekerjaan'            => ['nullable', 'string', 'max:100'],
            'status_kehidupan'     => ['nullable', 'in:Hidup,Meninggal,Pindah'],
        ], [
            'nik.required'         => 'NIK wajib diisi.',
            'nik.unique'           => 'NIK sudah terdaftar untuk penduduk lain.',
            'nama.required'        => 'Nama wajib diisi.',
            'jenis_kelamin.in'     => 'Jenis kelamin tidak valid.',
        ]);

        if (!empty($validated['tanggal_lahir'])) {
            $validated['usia'] = Carbon::parse($validated['tanggal_lahir'])->age;
        }

        if (!empty($validated['rt'])) {
            $validated['rt'] = str_pad((string) $validated['rt'], 3, '0', STR_PAD_LEFT);
        }
        if (!empty($validated['rw'])) {
            $validated['rw'] = str_pad((string) $validated['rw'], 3, '0', STR_PAD_LEFT);
        }

        $penduduk->update($validated);

        return redirect()
            ->route('penduduk.index')
            ->with('success', 'Data penduduk berhasil diperbarui.');
    }

    public function markPindah(Penduduk $penduduk)
    {
        $nama = $penduduk->nama;

        \DB::table('penduduks')
            ->where('id', $penduduk->id)
            ->update(['status_kehidupan' => 'Pindah', 'updated_at' => now()]);

        return redirect()
            ->route('penduduk.index', ['status_kehidupan' => ''])
            ->with('success', "Penduduk {$nama} berhasil ditandai Pindah. Data tetap tersimpan di database.");
    }

    public function destroy(Penduduk $penduduk)
    {
        $nama = $penduduk->nama;
        $penduduk->delete();

        return redirect()
            ->route('penduduk.index')
            ->with('success', "Data penduduk {$nama} berhasil dihapus.");
    }

    public function cariIstri(Request $request)
    {
        $kode = trim((string) $request->query('kode_keluarga', ''));

        if ($kode === '') {
            return response()->json(null);
        }

        $istri = Penduduk::query()
            ->select([
                'id', 'nik', 'nama', 'pekerjaan',
                'rt', 'rw', 'alamat', 'dusun',
                'kode_keluarga', 'nama_kepala_keluarga',
            ])
            ->where('kode_keluarga', $kode)
            ->whereIn('hubungan', ['Istri', 'istri', 'ISTRI'])
            ->where('status_kehidupan', 'Hidup')
            ->first();

        return response()->json($istri);
    }

    public function searchKepalaKeluarga(Request $request)
{
    try {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $rows = Penduduk::query()
            ->select([
                'kode_keluarga',
                'nama_kepala_keluarga',
                'alamat',
                'rt',
                'rw',
                'dusun',
            ])
            ->whereNotNull('kode_keluarga')
            ->where('kode_keluarga', '!=', '')
            ->whereNotNull('nama_kepala_keluarga')
            ->where('nama_kepala_keluarga', '!=', '')
            ->where(function ($w) use ($q) {
                $w->where('nama_kepala_keluarga', 'like', '%' . $q . '%')
                  ->orWhere('kode_keluarga', 'like', '%' . $q . '%')
                  ->orWhere('nama', 'like', '%' . $q . '%');
            })
            ->orderBy('nama_kepala_keluarga')
            ->get()
            ->groupBy('kode_keluarga')
            ->map(function ($group) {
                $first = $group->first();
                $maxNoUrut = (int) $group->max('no_urut');

                return [
                    'kode_keluarga' => $first->kode_keluarga,
                    'nama_kepala_keluarga' => $first->nama_kepala_keluarga,
                    'alamat' => $first->alamat,
                    'rt' => $first->rt,
                    'rw' => $first->rw,
                    'dusun' => $first->dusun,
                    'next_no_urut' => $maxNoUrut + 1,
                ];
            })
            ->values()
            ->take(10);

        return response()->json($rows);
    } catch (\Throwable $e) {
        \Log::error('searchKepalaKeluarga gagal: ' . $e->getMessage());

        return response()->json([
            'message' => 'Terjadi kesalahan saat mencari kepala keluarga.',
        ], 500);
    }
}

    /**
     * Build header mapping from various possible column names
     * (SUDAH DIPERBAIKI: pakai normalizeHeader untuk header dan alias)
     */
    private function buildHeaderMap(array $header): array
    {
        $map = [];

        $aliases = [
            'no_urut' => ['no', 'no.', 'nomor', 'no urut'],

            'kode_keluarga' => [
                'kode keluarga', 'kode_keluarga', 'no kk', 'nomor kk', 'kk', 'kodekeluarga'
            ],

            'nama_kepala_keluarga' => [
                'nama kepala keluarga', 'nama kk', 'kepala keluarga', 'namakepalakeluarga'
            ],

            'nama' => [
                'nama', 'nama anggota', 'nama anggota keluarga', 'namaanggotakeluarga'
            ],

            'nik' => [
                'nik', 'n i k'
            ],

            'jenis_kelamin' => ['jk', 'jenis kelamin', 'kelamin', 'jeniskelamin'],

            'hubungan' => ['hubungan', 'hub', 'hub. keluarga', 'hubkeluarga'],

            'tempat_lahir' => ['tempat lahir', 'tmp lahir', 'tempatlahir'],

            'tanggal_lahir' => ['tanggal lahir', 'tgl lahir', 'tgl. lahir', 'tanggallahir'],

            'usia' => ['usia', 'umur'],

            'status_perkawinan' => ['status', 'status perkawinan', 'status kawin', 'statusperkawinan'],

            'golongan_darah' => ['gdarah', 'gol darah', 'golongan darah', 'gol. darah', 'goldarah', 'golongandarah'],

            'agama' => ['agama'],

            'kewarganegaraan' => ['warga negara', 'kewarganegaraan', 'wn', 'kewarganegaraan'],

            'etnis' => ['etnis', 'suku', 'etnis/suku', 'etnis / suku', 'etnissuku'],

            'pendidikan' => ['pendidikan', 'pend', 'pend.', 'pendidikan'],

            'pekerjaan' => ['pekerjaan', 'pek', 'pek.', 'pekerjaan'],

            'rt' => ['rt'],
            'rw' => ['rw'],
            'dusun' => ['dusun', 'nama dusun', 'namadusun'],
            'alamat' => ['alamat'],
        ];

        $aliasNormalized = [];
        foreach ($aliases as $field => $possibleNames) {
            foreach ($possibleNames as $name) {
                $aliasNormalized[$field][] = $this->normalizeHeader((string) $name);
            }
        }

        foreach ($header as $index => $col) {
            $normalized = $this->normalizeHeader((string) $col);

            foreach ($aliasNormalized as $field => $possibleNorms) {
                if (in_array($normalized, $possibleNorms, true)) {
                    $map[$index] = $field;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Normalize header agar robust terhadap spasi/tanda baca:
     * "N I K" -> "nik"
     * "Etnis / Suku" -> "etnissuku"
     * "Kode Keluarga" -> "kodekeluarga"
     */
    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/\s+/', '', $h);
        $h = str_replace(['.', '/', '\\', '-', '_', '(', ')'], '', $h);
        return $h;
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
        if (!array_key_exists($index, $row)) {
            continue;
        }

        $value = $this->cleanCellValue($row[$index]);

        if ($value === '' || $value === null) {
            continue;
        }

        switch ($field) {
            case 'kode_keluarga':
                $data[$field] = ltrim((string) $value, "'");
                break;

            case 'nik':
    $rawNik = trim((string) $value);

    if (
        stripos($rawNik, 'E+') !== false ||
        stripos($rawNik, 'E-') !== false ||
        stripos($rawNik, 'e+') !== false ||
        stripos($rawNik, 'e-') !== false
    ) {
        $data[$field] = null;
        break;
    }

    $nik = preg_replace('/\D+/', '', $rawNik);

    if ($nik === '' || strlen($nik) > 20) {
        $data[$field] = null;
    } else {
        $data[$field] = $nik;
    }
    break;

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

            case 'golongan_darah':
                $data[$field] = $this->normalizeGolonganDarah($value);
                break;

            case 'no_urut':
            case 'usia':
                $numeric = preg_replace('/[^\d]/', '', (string) $value);
                $data[$field] = $numeric !== '' ? (int) $numeric : null;
                break;

            default:
                $data[$field] = $value;
                break;
        }
    }

    if (!empty($data['nama']) && empty($data['nama_kepala_keluarga']) && (int) ($data['no_urut'] ?? 0) === 1) {
        $data['nama_kepala_keluarga'] = $data['nama'];
    }

    if ((int) ($data['no_urut'] ?? 0) === 1 && empty($data['hubungan'])) {
        $data['hubungan'] = 'Kepala Keluarga';
    }

    return $data;
}

private function cleanCellValue($value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = (string) $value;

    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = str_replace("\xc2\xa0", ' ', $value);
    $value = str_replace('&nbsp;', ' ', $value);
    $value = trim($value);

    $value = preg_replace('/\s+/', ' ', $value);

    if ($value === '' || strtolower($value) === 'null' || strtolower($value) === 'nbsp') {
        return null;
    }

    return $value;
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
        if (in_array($v, ['L', 'LAKI-LAKI', 'LAKI', 'MALE', 'M'], true)) return 'L';
        if (in_array($v, ['P', 'PEREMPUAN', 'FEMALE', 'F'], true)) return 'P';
        return null;
    }

    private function normalizeGolonganDarah($value): ?string
{
    $v = strtoupper(trim((string) $value));

    if ($v === '' || in_array($v, ['TIDAK TAHU', 'TIDAKTAHU', '-', '--', 'NULL'], true)) {
        return null;
    }

    if (in_array($v, ['A', 'B', 'AB', 'O'], true)) {
        return $v;
    }

    return null;
}

    /**
     * Parse tanggal lahir from various formats
     */
    private function parseTanggalLahir($value): ?string
{
    $value = $this->cleanCellValue($value);
    if (!$value) return null;

    if (is_numeric($value) && (float)$value > 1000) {
        $unix = ((float)$value - 25569) * 86400;
        $date = gmdate('Y-m-d', (int)$unix);
        if ($date) return $date;
    }

    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d',
        'd-m-Y',
        'd/m/Y',
        'd.m.Y',
        'd-M-Y',
        'd M Y',
    ];

    foreach ($formats as $format) {
        $date = \DateTime::createFromFormat($format, $value);
        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d');
        }
    }

    return null;
}
}
