<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LetterCounter;
use Illuminate\Support\Facades\DB;

class LetterCounterController extends Controller
{
    private function monthToRoman(int $monthNumber): string
    {
        $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        return $romans[max(1, min(12, $monthNumber)) - 1];
    }

    public function show(string $templateSlug)
    {
        $row = LetterCounter::firstWhere('template_slug', $templateSlug);
        $now = now();

        return response()->json([
            'count' => $row ? (int) $row->count : 0,
            'monthRoman' => $this->monthToRoman((int) $now->format('n')),
            'year' => (int) $now->format('Y'),
        ]);
    }

    public function increment(Request $request, string $templateSlug)
    {
        $newCount = DB::transaction(function () use ($templateSlug) {
            $row = LetterCounter::where('template_slug', $templateSlug)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                $row = LetterCounter::create(['template_slug' => $templateSlug, 'count' => 0]);
            }

            $row->count = $row->count + 1;
            $row->save();

            return (int) $row->count;
        });

        $now = now();

        return response()->json([
            'count' => $newCount,
            'monthRoman' => $this->monthToRoman((int) $now->format('n')),
            'year' => (int) $now->format('Y'),
        ]);
    }
}
