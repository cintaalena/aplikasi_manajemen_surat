<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\LetterCounter;
use App\Models\Penduduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LetterController extends Controller
{
    private function monthToRoman(int $monthNumber): string
    {
        $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        return $romans[max(1, min(12, $monthNumber)) - 1];
    }

    private function buildNoSurat(int $urut, string $indexCode, string $monthRoman, int $year): string
    {
        return "{$urut}/Kel.Ftbs.{$indexCode}/{$monthRoman}/{$year}";
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return mb_strtolower($name);
    }

    private function mustExistInPenduduk(string $templateSlug): bool
    {
        return in_array($templateSlug, [
            'keterangan-domisili',
            'keterangan-kematian',
            'keterangan-pindah',
        ], true);
    }

    /**
     * FINALIZE = increment counter (per template) + simpan record surat final.
     * Dipanggil saat user klik "Cetak" / "Save as PDF".
     */
    public function finalize(Request $request, string $templateSlug)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'index_code' => ['required', 'string', 'max:50'],
            'payload' => ['required', 'array'],
        ]);

        if ($this->mustExistInPenduduk($templateSlug)) {
            $payload = $validated['payload'] ?? [];
            $pendudukId = $payload['penduduk_id'] ?? null;
            $namaInput = $this->normalizeName($payload['nama'] ?? '');

            if (!$pendudukId || $namaInput === '') {
                return response()->json([
                    'message' => 'nama ini tidak terdaftar di database penduduk kelurahan fatubesi',
                ], 422);
            }

            $penduduk = Penduduk::query()
                ->select('id', 'nama')
                ->find($pendudukId);

            if (!$penduduk) {
                return response()->json([
                    'message' => 'nama ini tidak terdaftar di database penduduk kelurahan fatubesi',
                ], 422);
            }

            $namaDb = $this->normalizeName($penduduk->nama);

            if ($namaDb !== $namaInput) {
                return response()->json([
                    'message' => 'nama ini tidak terdaftar di database penduduk kelurahan fatubesi',
                ], 422);
            }
        }

        $now = now();
        $monthRoman = $this->monthToRoman((int) $now->format('n'));
        $year = (int) $now->format('Y');

        $letter = DB::transaction(function () use ($templateSlug, $validated, $monthRoman, $year, $now) {
            $counter = LetterCounter::where('template_slug', $templateSlug)->lockForUpdate()->first();

            if (!$counter) {
                $counter = LetterCounter::create([
                    'template_slug' => $templateSlug,
                    'count' => 0,
                ]);
            }

            $counter->count = $counter->count + 1;
            $counter->save();

            $urut = (int) $counter->count;
            $indexCode = (string) $validated['index_code'];
            $noSurat = $this->buildNoSurat($urut, $indexCode, $monthRoman, $year);

            return Letter::create([
                'template_slug' => $templateSlug,
                'title' => $validated['title'],
                'no_surat' => $noSurat,

                'index_code' => $indexCode,
                'urut' => $urut,
                'month_roman' => $monthRoman,
                'year' => $year,

                'payload' => $validated['payload'],

                'printed_at' => $now,
                'printed_by' => auth()->id(),
            ]);
        });

        // Jika surat kematian → otomatis tandai penduduk sebagai Meninggal
        if ($templateSlug === 'keterangan-kematian') {
            $pendudukId = $validated['payload']['penduduk_id'] ?? null;
            if ($pendudukId) {
                Penduduk::where('id', $pendudukId)
                    ->update(['status_kehidupan' => 'Meninggal']);
            }
        }

        // Jika surat pindah → hapus kepala keluarga + pengikut dari database
        if ($templateSlug === 'keterangan-pindah') {
            $pendudukId = $validated['payload']['penduduk_id'] ?? null;
            if ($pendudukId) {
                Penduduk::where('id', $pendudukId)->delete();
            }

            $pengikut = $validated['payload']['pengikut'] ?? [];
            foreach ($pengikut as $p) {
                $nik = trim((string) ($p['nik'] ?? ''));
                if ($nik !== '') {
                    Penduduk::where('nik', $nik)->delete();
                }
            }
        }

        // Jika surat kelahiran → otomatis daftarkan bayi ke database penduduk
        $pendudukCreated = false;
        if ($templateSlug === 'keterangan-kelahiran') {
            $payload  = $validated['payload'];
            $namaBayi = trim((string) ($payload['nama'] ?? ''));

            if ($namaBayi !== '') {
                try {
                    // Ambil data keluarga dari ayah (prioritas) atau ibu
                    $ayahId   = $payload['ayah_id'] ?? null;
                    $ibuId    = $payload['ibu_id']  ?? null;
                    $orangTua = null;

                    if ($ayahId) {
                        $orangTua = Penduduk::find($ayahId);
                    } elseif ($ibuId) {
                        $orangTua = Penduduk::find($ibuId);
                    }

                    if ($orangTua) {
                        $familyData = [
                            'kode_keluarga'   => $orangTua->kode_keluarga ?? '',
                            'alamat'          => $orangTua->alamat,
                            'rt'              => $orangTua->rt,
                            'rw'              => $orangTua->rw,
                            'dusun'           => $orangTua->dusun,
                            'kewarganegaraan' => $orangTua->kewarganegaraan,
                        ];
                    } else {
                        // Fallback: gunakan data yang diisi manual di form
                        $familyData = [
                            'kode_keluarga'   => $payload['kode_keluarga'] ?? '',
                            'alamat'          => $payload['alamat']        ?? '',
                            'rt'              => $payload['rt']            ?? '',
                            'rw'              => $payload['rw']            ?? '',
                            'dusun'           => $payload['dusun']         ?? '',
                            'kewarganegaraan' => $payload['kewarganegaraan'] ?? 'Warga Negara Indonesia',
                        ];
                    }

                    // Cari kepala keluarga sebenarnya dari kode_keluarga yang sudah ditentukan
                    $namaKepala = $payload['nama_kepala_keluarga'] ?? '';
                    if (!empty($familyData['kode_keluarga'])) {
                        $kepala = Penduduk::where('kode_keluarga', $familyData['kode_keluarga'])
                            ->where(function ($q) {
                                $q->where('hubungan', 'like', '%Kepala%')
                                  ->orWhere('no_urut', 1);
                            })
                            ->orderBy('no_urut')
                            ->first();

                        if ($kepala) {
                            $namaKepala = $kepala->nama;
                        } elseif ($orangTua) {
                            // Tidak ada baris kepala keluarga, gunakan nama_kepala_keluarga dari record orang tua
                            $namaKepala = $orangTua->nama_kepala_keluarga ?? $orangTua->nama;
                        }
                    }
                    $familyData['nama_kepala_keluarga'] = $namaKepala;

                    // Hitung no_urut: jumlah anggota keluarga saat ini + 1
                    $noUrut = 1;
                    if (!empty($familyData['kode_keluarga'])) {
                        $maxUrut = Penduduk::where('kode_keluarga', $familyData['kode_keluarga'])->max('no_urut');
                        $noUrut  = ($maxUrut ?? 0) + 1;
                    }

                    // Normalize jenis kelamin ke L/P
                    $jkRaw = (string) ($payload['jenisKelamin'] ?? '');
                    $jkDb  = null;
                    if (stripos($jkRaw, 'l') === 0) $jkDb = 'L';
                    elseif (stripos($jkRaw, 'p') === 0) $jkDb = 'P';

                    // Parse tanggal lahir
                    $tglLahir = null;
                    if (!empty($payload['tanggalLahir'])) {
                        try {
                            $tglLahir = \Carbon\Carbon::parse($payload['tanggalLahir'])->format('Y-m-d');
                        } catch (\Throwable $e) {}
                    }
                    $usia = $tglLahir ? (int) \Carbon\Carbon::parse($tglLahir)->age : null;

                    Penduduk::create(array_merge($familyData, [
                        'no_urut'           => $noUrut,
                        'nik'               => null,
                        'nama'              => $namaBayi,
                        'jenis_kelamin'     => $jkDb,
                        'hubungan'          => 'Anak Kandung',
                        'tempat_lahir'      => $payload['tempatLahir'] ?? null,
                        'tanggal_lahir'     => $tglLahir,
                        'usia'              => $usia,
                        'status_perkawinan' => 'Belum Kawin',
                        'agama'             => $payload['agama'] ?? null,
                        'status_kehidupan'  => 'Hidup',
                    ]));

                    $pendudukCreated = true;
                } catch (\Throwable $e) {
                    \Log::warning('Gagal mendaftarkan bayi ke database penduduk: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'id'              => $letter->id,
            'noSurat'         => $letter->no_surat,
            'urut'            => $letter->urut,
            'monthRoman'      => $letter->month_roman,
            'year'            => $letter->year,
            'pendudukCreated' => $pendudukCreated,
        ]);
    }
}