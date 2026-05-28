<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LetterDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LetterDocumentController extends Controller
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    private const MAX_SIZE_BYTES = 5 * 1024 * 1024; // 5 MB

    /**
     * Upload satu file dokumen pendukung surat.
     * Mengembalikan ID sementara (record tanpa letter_id) yang nanti di-link saat finalize.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file'      => ['required', 'file', 'max:5120'],
            'doc_key'   => ['required', 'string', 'max:80'],
            'doc_label' => ['required', 'string', 'max:200'],
        ]);

        $file = $request->file('file');

        // SECURITY: Validasi MIME dari konten nyata (bukan hanya extension)
        $realMime = $file->getMimeType();
        if (!in_array($realMime, self::ALLOWED_MIMES, true)) {
            Log::warning('LetterDocument upload: MIME ditolak', [
                'mime'    => $realMime,
                'name'    => $file->getClientOriginalName(),
                'ip'      => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);
            return response()->json(['message' => 'Tipe file tidak diizinkan. Gunakan JPG, PNG, WEBP, atau PDF.'], 422);
        }

        // SECURITY: Cek ukuran file
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            return response()->json(['message' => 'Ukuran file terlalu besar. Maksimal 5 MB.'], 422);
        }

        // SECURITY: Untuk gambar — cek konten agar tidak ada PHP/script injection
        if (str_starts_with($realMime, 'image/')) {
            $content = file_get_contents($file->getRealPath());
            if (preg_match('/<\?php|<\?=|<script/i', $content)) {
                Log::alert('LetterDocument upload: konten berbahaya terdeteksi', [
                    'name'    => $file->getClientOriginalName(),
                    'ip'      => $request->ip(),
                    'user_id' => $request->user()?->id,
                ]);
                return response()->json(['message' => 'File mengandung konten yang tidak diizinkan.'], 422);
            }
        }

        // Simpan dengan nama acak agar tidak bisa ditebak
        $ext      = strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $filename = Str::uuid() . '.' . $ext;
        $dir      = 'dokumen-surat/' . now()->format('Y/m');
        $path     = $file->storeAs($dir, $filename, 'public');

        $doc = LetterDocument::create([
            'letter_id'     => null, // akan di-link saat finalize
            'doc_key'       => $request->input('doc_key'),
            'doc_label'     => $request->input('doc_label'),
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $realMime,
            'file_size'     => $file->getSize(),
            'uploaded_by'   => $request->user()?->id, // SECURITY (A01): Track uploader
        ]);

        return response()->json([
            'id'  => $doc->id,
            'url' => $doc->url,
        ], 201);
    }

    /**
     * Stream file dokumen langsung dari disk — bypass symlink & APP_URL.
     * Hanya user yang login yang bisa mengakses.
     */
    public function file(LetterDocument $document)
    {
        $disk = Storage::disk('public');
        $path = $document->file_path;

        if (!$disk->exists($path)) {
            abort(404, 'File tidak ditemukan di server.');
        }

        $mime     = $document->mime_type ?: 'application/octet-stream';
        $filename = $document->original_name ?? 'dokumen';
        $size     = $disk->size($path);

        return response()->stream(
            function () use ($disk, $path) {
                $stream = $disk->readStream($path);
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type'        => $mime,
                'Content-Length'      => $size,
                'Content-Disposition' => 'inline; filename="' . rawurlencode($filename) . '"',
                'Cache-Control'       => 'private, max-age=3600',
                'X-Accel-Buffering'   => 'no',
            ]
        );
    }

    /**
     * Hapus dokumen (hanya jika belum ter-link ke letter).
     *
     * SECURITY (A01): Object-level authorization — only the uploader or an admin
     * may delete an unlinked document to prevent unauthorized deletion.
     */
    public function destroy(LetterDocument $document, \Illuminate\Http\Request $request)
    {
        if ($document->letter_id !== null) {
            return response()->json(['message' => 'Dokumen sudah terikat pada arsip surat dan tidak dapat dihapus.'], 403);
        }

        // SECURITY (A01): Only the uploader or admin can delete pending documents
        $user = $request->user();
        if ($document->uploaded_by !== null
            && $document->uploaded_by !== $user->id
            && $user->role !== 'admin'
        ) {
            return response()->json(['message' => 'Anda tidak berhak menghapus dokumen ini.'], 403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Dokumen dihapus.']);
    }
}
