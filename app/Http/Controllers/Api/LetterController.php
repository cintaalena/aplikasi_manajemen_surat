<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\LetterCounter;
use App\Models\LetterDocument;
use App\Models\LetterNotification;
use App\Models\Penduduk;
use App\Models\User;
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
            'title'    => ['required', 'string', 'max:150'],
            'index_code' => ['required', 'string', 'max:50'],
            'payload'  => ['required', 'array'],
            'doc_ids'  => ['sometimes', 'array'],
            'doc_ids.*' => ['integer', 'exists:letter_documents,id'],
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

        // Validasi pengikut surat pindah — semua harus ada di database
        if ($templateSlug === 'keterangan-pindah') {
            $payload  = $validated['payload'] ?? [];
            $pengikut = $payload['pengikut'] ?? [];
            foreach ($pengikut as $idx => $p) {
                $pId   = $p['penduduk_id'] ?? null;
                $pNama = $this->normalizeName($p['nama'] ?? '');
                $no    = $idx + 1;

                if (!$pId || $pNama === '') {
                    return response()->json([
                        'message' => "Pengikut {$no} tidak terdaftar di database penduduk Kelurahan Fatubesi.",
                    ], 422);
                }

                $pRec = Penduduk::select('id', 'nama')->find($pId);
                if (!$pRec) {
                    return response()->json([
                        'message' => "Pengikut {$no} tidak ditemukan di database penduduk Kelurahan Fatubesi.",
                    ], 422);
                }

                if ($this->normalizeName($pRec->nama) !== $pNama) {
                    return response()->json([
                        'message' => "Nama pengikut {$no} tidak cocok dengan data di database.",
                    ], 422);
                }
            }
        }

        $now = now();
        $monthRoman = $this->monthToRoman((int) $now->format('n'));
        $year = (int) $now->format('Y');

        try {
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
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate no_surat — cari urut berikutnya yang benar-benar belum ada (cek global)
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'no_surat')) {
                $indexCode = (string) $validated['index_code'];

                $nextUrut = DB::transaction(function () use ($templateSlug, $indexCode, $monthRoman, $year) {
                    $counter = LetterCounter::where('template_slug', $templateSlug)->lockForUpdate()->first();

                    // Titik mulai: ambil yang lebih besar antara counter saat ini+1 atau max urut template+1
                    $maxForTemplate = Letter::where('template_slug', $templateSlug)->max('urut') ?? 0;
                    $start = max(
                        ($counter ? (int) $counter->count : 0) + 1,
                        $maxForTemplate + 1
                    );

                    // Loop sampai menemukan noSurat yang belum ada sama sekali di tabel letters
                    $urut = $start;
                    while (Letter::where('no_surat', "{$urut}/Kel.Ftbs.{$indexCode}/{$monthRoman}/{$year}")->exists()) {
                        $urut++;
                    }

                    // Setel counter ke urut-1 agar finalize berikutnya menghasilkan urut ini
                    if ($counter) {
                        $counter->count = $urut - 1;
                        $counter->save();
                    } else {
                        LetterCounter::create(['template_slug' => $templateSlug, 'count' => $urut - 1]);
                    }

                    return $urut;
                });

                $nextNoSurat = $this->buildNoSurat($nextUrut, $indexCode, $monthRoman, $year);

                return response()->json([
                    'message'     => 'Nomor surat tersebut sudah digunakan oleh surat lain.',
                    'duplicate'   => true,
                    'nextNoSurat' => $nextNoSurat,
                ], 409);
            } else {
                throw $e;
            }
        }

        // Link dokumen persyaratan yang sudah di-upload ke letter ini
        $docIds = $validated['doc_ids'] ?? [];
        if (!empty($docIds)) {
            LetterDocument::whereIn('id', $docIds)
                ->whereNull('letter_id')
                ->update(['letter_id' => $letter->id]);
        }

        // Jika surat kematian → otomatis tandai penduduk sebagai Meninggal
        if ($templateSlug === 'keterangan-kematian') {
            $pendudukId = $validated['payload']['penduduk_id'] ?? null;
            if ($pendudukId) {
                Penduduk::where('id', $pendudukId)
                    ->update(['status_kehidupan' => 'Meninggal']);
            }
        }

        // Jika surat pindah → tandai kepala keluarga + pengikut sebagai Pindah (tidak dihapus)
        if ($templateSlug === 'keterangan-pindah') {
            $pendudukId = $validated['payload']['penduduk_id'] ?? null;
            if ($pendudukId) {
                \Illuminate\Support\Facades\DB::table('penduduks')
                    ->where('id', $pendudukId)
                    ->whereNull('deleted_at')
                    ->update(['status_kehidupan' => 'Pindah', 'updated_at' => now()]);
            }

            $pengikut = $validated['payload']['pengikut'] ?? [];
            $pengikutIds = array_filter(
                array_map(fn($p) => isset($p['penduduk_id']) ? (int)$p['penduduk_id'] : null, $pengikut)
            );
            if (!empty($pengikutIds)) {
                \Illuminate\Support\Facades\DB::table('penduduks')
                    ->whereIn('id', array_values($pengikutIds))
                    ->whereNull('deleted_at')
                    ->update(['status_kehidupan' => 'Pindah', 'updated_at' => now()]);
            }
        }

        // Jika surat kelahiran → otomatis simpan data bayi ke tabel penduduk
        if ($templateSlug === 'keterangan-kelahiran') {
            $p = $validated['payload'];
            $nama = trim((string) ($p['nama'] ?? ''));
            $jk   = ($p['jenisKelamin'] ?? '') === 'Laki-laki' ? 'L' : (($p['jenisKelamin'] ?? '') === 'Perempuan' ? 'P' : null);

            if ($nama !== '' && $jk !== null) {
                $nikBayi = trim((string) ($p['nik'] ?? ''));
                $nikBayi = $nikBayi !== '' ? $nikBayi : null;

                // Hindari duplikat jika sudah pernah disimpan (NIK sama)
                $sudahAda = $nikBayi ? Penduduk::where('nik', $nikBayi)->exists() : false;

                if (!$sudahAda) {
                    // ── Tentukan keluarga bayi ─────────────────────────────
                    // Prioritas 1: Ayah terdata di database
                    $kodeKeluarga = null;
                    $namaKepala   = null;
                    $dusun        = null;
                    $alamat       = trim((string) ($p['alamat'] ?? '')) ?: null;
                    $rtRaw        = preg_replace('/\D/', '', (string) ($p['rt'] ?? ''));
                    $rwRaw        = preg_replace('/\D/', '', (string) ($p['rw'] ?? ''));
                    $rt           = $rtRaw !== '' ? str_pad($rtRaw, 3, '0', STR_PAD_LEFT) : null;
                    $rw           = $rwRaw !== '' ? str_pad($rwRaw, 3, '0', STR_PAD_LEFT) : null;

                    $ayahId = $p['ayah_id'] ?? null;
                    $ibuId  = $p['ibu_id']  ?? null;

                    if ($ayahId) {
                        // Ayah terdata → masuk keluarga ayah
                        $ayah = Penduduk::select(
                            'kode_keluarga', 'nama_kepala_keluarga', 'dusun', 'alamat', 'rt', 'rw'
                        )->find($ayahId);

                        if ($ayah) {
                            $kodeKeluarga = $ayah->kode_keluarga;
                            $namaKepala   = $ayah->nama_kepala_keluarga;
                            $dusun        = $ayah->dusun;
                            $alamat       = $alamat ?: $ayah->alamat;
                            $rt           = $rt ?: ($ayah->rt ? str_pad($ayah->rt, 3, '0', STR_PAD_LEFT) : null);
                            $rw           = $rw ?: ($ayah->rw ? str_pad($ayah->rw, 3, '0', STR_PAD_LEFT) : null);
                        }
                    } elseif ($ibuId) {
                        // Ayah tidak terdata, tapi ibu terdata → masuk keluarga ibu
                        $ibu = Penduduk::select(
                            'kode_keluarga', 'nama_kepala_keluarga', 'dusun', 'alamat', 'rt', 'rw'
                        )->find($ibuId);

                        if ($ibu) {
                            $kodeKeluarga = $ibu->kode_keluarga;
                            $namaKepala   = $ibu->nama_kepala_keluarga;
                            $dusun        = $ibu->dusun;
                            $alamat       = $alamat ?: $ibu->alamat;
                            $rt           = $rt ?: ($ibu->rt ? str_pad($ibu->rt, 3, '0', STR_PAD_LEFT) : null);
                            $rw           = $rw ?: ($ibu->rw ? str_pad($ibu->rw, 3, '0', STR_PAD_LEFT) : null);
                        }
                    } else {
                        // Tidak ada ayah/ibu terdata → pakai data payload langsung
                        $kodeKeluarga = trim((string) ($p['kode_keluarga'] ?? '')) ?: null;
                        $namaKepala   = trim((string) ($p['nama_kepala_keluarga'] ?? '')) ?: null;
                        $dusun        = trim((string) ($p['dusun'] ?? '')) ?: null;
                    }

                    // Hitung no_urut berikutnya dalam KK yang sama
                    $noUrut = 1;
                    if ($kodeKeluarga) {
                        $maxUrut = Penduduk::where('kode_keluarga', $kodeKeluarga)
                            ->max('no_urut');
                        $noUrut = $maxUrut ? ((int) $maxUrut + 1) : 1;
                    }

                    Penduduk::create([
                        'nik'                  => $nikBayi,
                        'nama'                 => $nama,
                        'jenis_kelamin'        => $jk,
                        'agama'                => trim((string) ($p['agama'] ?? '')) ?: null,
                        'tempat_lahir'         => trim((string) ($p['tempatLahir'] ?? '')) ?: null,
                        'tanggal_lahir'        => trim((string) ($p['tanggalLahir'] ?? '')) ?: null,
                        'usia'                 => 0,
                        'no_urut'              => $noUrut,
                        'hubungan'             => 'Anak',
                        'status_perkawinan'    => 'Belum Kawin',
                        'pekerjaan'            => null,
                        'alamat'               => $alamat,
                        'rt'                   => $rt,
                        'rw'                   => $rw,
                        'dusun'                => $dusun,
                        'kode_keluarga'        => $kodeKeluarga,
                        'nama_kepala_keluarga' => $namaKepala,
                        'kewarganegaraan'      => 'WNI',
                        'status_kehidupan'     => 'Hidup',
                    ]);
                }
            }
        }

        $this->notifyLurah($letter);

        return response()->json([
            'id' => $letter->id,
            'noSurat' => $letter->no_surat,
            'urut' => $letter->urut,
            'monthRoman' => $letter->month_roman,
            'year' => $letter->year,
        ]);
    }

    private function notifyLurah(Letter $letter): void
    {
        $lurahUsers = User::where('role', 'lurah')->where('is_active', true)->get(['id']);

        foreach ($lurahUsers as $lurah) {
            LetterNotification::create([
                'user_id'   => $lurah->id,
                'letter_id' => $letter->id,
                'message'   => 'Surat baru dicetak: ' . $letter->no_surat . ' — ' . $letter->title,
                'is_read'   => false,
            ]);
        }
    }
}