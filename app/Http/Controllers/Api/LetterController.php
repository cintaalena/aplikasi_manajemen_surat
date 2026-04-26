<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\LetterCounter;
use App\Models\Penduduk;
use Illuminate\Database\UniqueConstraintViolationException;
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

        try {
            $letter = DB::transaction(function () use ($templateSlug, $validated, $monthRoman, $year, $now) {
                $counter = LetterCounter::where('template_slug', $templateSlug)->lockForUpdate()->first();

                if (!$counter) {
                    $counter = LetterCounter::create([
                        'template_slug' => $templateSlug,
                        'count' => 0,
                    ]);
                }

                // Sinkronisasi counter dengan data aktual di tabel letters
                // agar tidak bentrok jika counter pernah di-reset atau tidak sinkron
                $maxUrut = Letter::where('template_slug', $templateSlug)
                    ->where('month_roman', $monthRoman)
                    ->where('year', $year)
                    ->max('urut') ?? 0;

                $nextUrut = max((int) $counter->count + 1, (int) $maxUrut + 1);
                $counter->count = $nextUrut;
                $counter->save();

                $urut = $nextUrut;
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
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'Nomor surat ini sudah ada di arsip. Kemungkinan surat sudah pernah disimpan sebelumnya.',
            ], 409);
        }

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

        return response()->json([
            'id' => $letter->id,
            'noSurat' => $letter->no_surat,
            'urut' => $letter->urut,
            'monthRoman' => $letter->month_roman,
            'year' => $letter->year,
        ]);
    }
}