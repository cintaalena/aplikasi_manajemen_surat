<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterDocument;
use App\Models\LetterNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            ->with('printedBy:id,name')
            ->with('documents:id,letter_id,doc_key,doc_label,original_name,mime_type,file_size,file_path')
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

    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_surat'  => ['required', 'string', 'max:100', 'unique:letters,no_surat'],
            'title'     => ['required', 'string', 'max:255'],
            'files'     => ['required', 'array', 'min:1', 'max:10'],
            'files.*'   => ['file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ], [
            'no_surat.required' => 'Nomor surat wajib diisi.',
            'no_surat.unique'   => 'Nomor surat sudah ada di arsip.',
            'title.required'    => 'Judul surat wajib diisi.',
            'files.required'    => 'File berkas surat wajib diupload minimal 1 file.',
            'files.min'         => 'File berkas surat wajib diupload minimal 1 file.',
            'files.*.mimes'     => 'File harus berupa JPG, PNG, WEBP, atau PDF.',
            'files.*.max'       => 'Ukuran file maksimal 5 MB.',
        ]);

        $letter = Letter::create([
            'no_surat'   => $validated['no_surat'],
            'title'      => $validated['title'],
            'is_manual'  => true,
            'printed_at' => now(),
            'printed_by' => $request->user()?->id,
        ]);

        // Simpan file yang di-upload langsung bersama form
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $realMime = $file->getMimeType();

                // Security: tolak MIME yang tidak diizinkan
                if (!in_array($realMime, self::ALLOWED_MIMES, true)) {
                    continue;
                }

                $ext      = strtolower($file->getClientOriginalExtension()) ?: 'bin';
                $filename = Str::uuid() . '.' . $ext;
                $dir      = 'dokumen-surat/' . now()->format('Y/m');
                $path     = $file->storeAs($dir, $filename, 'public');

                LetterDocument::create([
                    'letter_id'     => $letter->id,
                    'doc_key'       => 'surat_masuk',
                    'doc_label'     => 'Berkas Surat Masuk',
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $realMime,
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        $this->notifyLurah($letter);

        return back()->with('success', 'Surat berhasil ditambahkan ke arsip.');
    }

    private function notifyLurah(Letter $letter): void
    {
        $lurahUsers = User::where('role', 'lurah')->where('is_active', true)->get(['id']);

        foreach ($lurahUsers as $lurah) {
            LetterNotification::create([
                'user_id'   => $lurah->id,
                'letter_id' => $letter->id,
                'message'   => 'Arsip baru ditambahkan: ' . $letter->no_surat . ' — ' . $letter->title,
                'is_read'   => false,
            ]);
        }
    }

    public function show(Letter $letter)
    {
        $letter->load('printedBy');

        return Inertia::render('ArsipSurat/Show', [
            'letter' => [
                'id'            => $letter->id,
                'template_slug' => $letter->template_slug,
                'title'         => $letter->title,
                'no_surat'      => $letter->no_surat,
                'payload'       => $letter->payload ?? [],
                'printed_at'    => $letter->printed_at,
                'is_manual'     => $letter->is_manual,
                'printed_by'    => $letter->printedBy
                    ? ['id' => $letter->printedBy->id, 'name' => $letter->printedBy->name]
                    : null,
            ],
        ]);
    }
}
