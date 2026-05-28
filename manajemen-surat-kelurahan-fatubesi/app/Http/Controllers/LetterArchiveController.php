<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LetterArchiveController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $letters = Letter::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('no_surat', 'like', "%{$q}%")
                      ->orWhere('title', 'like', "%{$q}%");
            })
            ->orderByDesc('printed_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('ArsipSurat/Index', [
            'q' => $q,
            'letters' => $letters,
        ]);
    }
}
