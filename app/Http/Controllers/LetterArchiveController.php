<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LetterArchiveController extends Controller
{
    /** Label untuk tiap template slug. */
    private array $templateLabels = [
        'keterangan-domisili'      => 'Surat Keterangan Domisili',
        'keterangan-kematian'      => 'Surat Keterangan Kematian',
        'keterangan-kelahiran'     => 'Surat Keterangan Kelahiran',
        'keterangan-usaha'         => 'Surat Keterangan Usaha',
        'keterangan-kelakuan-baik' => 'Surat Keterangan Kelakuan Baik',
        'keterangan-umum'          => 'Surat Keterangan (Umum)',
    ];

    public function index(Request $request)
    {
        $no_surat      = trim((string) $request->query('no_surat', ''));
        $title         = trim((string) $request->query('title', ''));
        $template_slug = trim((string) $request->query('template_slug', ''));
        $date_from     = trim((string) $request->query('date_from', ''));
        $date_to       = trim((string) $request->query('date_to', ''));

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
            ->when($template_slug !== '', fn($query) =>
                $query->where('template_slug', $template_slug)
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

        // Daftar jenis surat yang ada di DB (untuk dropdown pilihan)
        $templateOptions = DB::table('letters')
            ->select('template_slug')
            ->whereNotNull('template_slug')
            ->distinct()
            ->orderBy('template_slug')
            ->pluck('template_slug')
            ->map(fn($slug) => [
                'value' => $slug,
                'label' => $this->templateLabels[$slug] ?? $slug,
            ])
            ->values()
            ->toArray();

        return Inertia::render('ArsipSurat/Index', [
            'filters' => [
                'no_surat'      => $no_surat,
                'title'         => $title,
                'template_slug' => $template_slug,
                'date_from'     => $date_from,
                'date_to'       => $date_to,
            ],
            'templateOptions' => $templateOptions,
            'letters'         => $letters,
        ]);
    }
}
