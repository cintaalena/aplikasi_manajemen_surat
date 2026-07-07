<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterDocument;
use App\Models\LetterNotification;
use App\Models\LetterView;
use App\Models\User;
use App\Support\LetterDocumentRequirements;
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

        $q = trim((string) $request->query('q', ''));

        $letters = Letter::query()
            ->select([
        'id',
        'template_slug',
        'title',
        'no_surat',
        'printed_at',
        'printed_by',
        'is_manual',
        'payload',
    ])
            ->with('printedBy:id,name')
            ->with('documents:id,letter_id,doc_key,doc_label,original_name,mime_type,file_size,file_path')
            ->with(['dispositions' => function ($q) {
                $q->select([
            'id',
            'letter_id',
            'from_user_id',
            'to_user_id',
            'status',
        ])
        ->with('fromUser:id,name')
        ->with('toUser:id,name');
    }])
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

        $letters->getCollection()->transform(function ($letter) {
            $existingKeys = $letter->documents->pluck('doc_key')->all();
            $letter->missing_required_docs = LetterDocumentRequirements::missingKeys(
                $letter->template_slug,
                $letter->payload ?? [],
                $existingKeys
            );
            return $letter;
        });

        $viewedIds = LetterView::where('user_id', $request->user()->id)
            ->whereIn('letter_id', $letters->pluck('id')->toArray())
            ->pluck('letter_id')
            ->toArray();

        return Inertia::render('ArsipSurat/Index', [
            'filters' => [
                'no_surat'  => $no_surat,
                'title'     => $title,
                'date_from' => $date_from,
                'date_to'   => $date_to,
            ],
            'letters'    => $letters,
            'viewed_ids' => $viewedIds,
        ]);
    }

    /**
     * Tandai surat sebagai sudah dilihat oleh user yang sedang login.
     */
    public function markViewed(Request $request, Letter $letter)
    {
        LetterView::firstOrCreate([
            'letter_id' => $letter->id,
            'user_id'   => $request->user()->id,
        ]);

        return response()->json(['ok' => true]);
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

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $realMime = $file->getMimeType();

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

        return redirect()->route('arsip-surat.index')
            ->with('success', 'Surat berhasil ditambahkan ke arsip.');
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
        $letter->load('printedBy', 'documents');

        $existingKeys = $letter->documents->pluck('doc_key')->all();

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
                    ? [
                        'id'      => $letter->printedBy->id,
                        'name'    => $letter->printedBy->name,
                        'nip'     => $letter->printedBy->nip,
                        'jabatan' => $letter->printedBy->jabatan,
                    ]
                    : null,
                // Penanda tangan yang benar-benar dipilih saat surat dibuat (bisa berbeda dari
                // printed_by kalau staf memilih Lurah/Kasie sebagai penanda tangan). Surat lama
                // sebelum fitur ini ada tidak punya snapshot ini, jadi jatuh ke printed_by.
                'signer'        => $letter->signer ?? ($letter->printedBy
                    ? [
                        'name'    => $letter->printedBy->name,
                        'nip'     => $letter->printedBy->nip,
                        'jabatan' => $letter->printedBy->jabatan,
                    ]
                    : null),
                'documents'     => $letter->documents->map(fn($doc) => [
                    'id'            => $doc->id,
                    'doc_key'       => $doc->doc_key,
                    'doc_label'     => $doc->doc_label,
                    'original_name' => $doc->original_name,
                    'mime_type'     => $doc->mime_type,
                    'file_size'     => $doc->file_size,
                    'url'           => $doc->url,
                ]),
                'missing_required_docs' => LetterDocumentRequirements::missingKeys(
                    $letter->template_slug,
                    $letter->payload ?? [],
                    $existingKeys
                ),
            ],
        ]);
    }

    /**
     * Render pratinjau/cetak surat dalam format Blade (untuk print).
     */
    public function pratinjau(Letter $letter)
    {
        $letter->load('printedBy');

        return view('letters.pratinjau', [
            'letter' => $letter,
        ]);
    }
}
