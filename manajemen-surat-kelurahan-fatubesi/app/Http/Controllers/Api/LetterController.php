<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\LetterCounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LetterController extends Controller
{
    private function monthToRoman(int $monthNumber): string
    {
        $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        return $romans[max(1, min(12, $monthNumber)) - 1];
    }

    private function buildNoSurat(int $urut, int $indexCode, string $monthRoman, int $year): string
    {
        return "{$urut}/Kel.Ftbs.{$indexCode}/{$monthRoman}/{$year}";
    }

    /**
     * FINALIZE = increment counter (per template) + simpan record surat final.
     * Dipanggil saat user klik "Cetak" / "Save as PDF".
     */
    public function finalize(Request $request, string $templateSlug)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'index_code' => ['required', 'integer', 'min:1'],
            'payload' => ['required', 'array'],
        ]);

        $now = now();
        $monthRoman = $this->monthToRoman((int) $now->format('n'));
        $year = (int) $now->format('Y');

        $letter = DB::transaction(function () use ($templateSlug, $validated, $monthRoman, $year, $now) {
            // lock counter row
            $counter = LetterCounter::where('template_slug', $templateSlug)->lockForUpdate()->first();
            if (!$counter) {
                $counter = LetterCounter::create(['template_slug' => $templateSlug, 'count' => 0]);
            }

            // increment hanya saat finalize
            $counter->count = $counter->count + 1;
            $counter->save();

            $urut = (int) $counter->count;
            $indexCode = (int) $validated['index_code'];
            $noSurat = $this->buildNoSurat($urut, $indexCode, $monthRoman, $year);

            // simpan surat final ke tabel letters
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

        return response()->json([
            'id' => $letter->id,
            'noSurat' => $letter->no_surat,
            'urut' => $letter->urut,
            'monthRoman' => $letter->month_roman,
            'year' => $letter->year,
        ]);
    }
}
