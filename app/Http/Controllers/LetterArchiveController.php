<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LetterArchiveController extends Controller
{
    public function index(Request $request)
    {
        $no_surat  = trim((string) $request->query('no_surat', ''));
        $title     = trim((string) $request->query('title', ''));
        $date_from = trim((string) $request->query('date_from', ''));
        $date_to   = trim((string) $request->query('date_to', ''));

        // Backward-compat: jika masih ada param `q` lama
        $q = trim((string) $request->query('q', ''));

        $letters = Letter::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('no_surat', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%");
                });
            })
            ->when($no_surat !== '', fn($query) =>
                $query->where('no_surat', 'like', "%{$no_surat}%")
            )
            ->when($title !== '', fn($query) =>
                $query->where('title', 'like', "%{$title}%")
            )
            ->when($date_from !== '', fn($query) =>
                $query->whereDate('printed_at', '>=', $date_from)
            )
            ->when($date_to !== '', fn($query) =>
                $query->whereDate('printed_at', '<=', $date_to)
            )
            ->orderByDesc('printed_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('ArsipSurat/Index', [
            'filters' => [
                'no_surat'  => $no_surat,
                'title'     => $title,
                'date_from' => $date_from,
                'date_to'   => $date_to,
            ],
            'letters' => $letters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_surat' => ['required', 'string', 'max:100', 'unique:letters,no_surat'],
            'title'    => ['required', 'string', 'max:255'],
        ], [
            'no_surat.required' => 'Nomor surat wajib diisi.',
            'no_surat.unique'   => 'Nomor surat sudah ada di arsip.',
            'title.required'    => 'Judul surat wajib diisi.',
        ]);

        Letter::create([
            'no_surat'   => $validated['no_surat'],
            'title'      => $validated['title'],
            'is_manual'  => true,
            'printed_at' => now(),
            'printed_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Surat berhasil ditambahkan ke arsip.');
    }
}
