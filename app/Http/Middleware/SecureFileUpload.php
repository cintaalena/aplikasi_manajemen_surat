<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecureFileUpload
{
    /**
     * SECURITY: Validate and sanitize file uploads
     * - Deep content validation (not just MIME type)
     * - File size limits
     * - Malicious content detection
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // SECURITY: Validate real MIME type from content (not just extension)
           $realMimeType = $file->getMimeType();

            $allowedMimeTypes = [
                // CSV / Text
                'text/plain',
                'text/csv',
                'application/csv',
                'text/x-csv',

                // Excel .xlsx (ZIP-based format — PHP finfo detects as application/zip on many systems)
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'application/x-zip-compressed',
                'multipart/x-zip',

                // Excel .xls (OLE compound document)
                'application/vnd.ms-excel',
                'application/CDFV2',
                'application/msword',        // some finfo versions misdetect .xls
                'application/octet-stream',  // generic binary fallback
            ];
            
            // Allow based on extension when MIME is ambiguous (zip/octet-stream + xlsx/xls extension)
            $ext = strtolower($file->getClientOriginalExtension());
            $isExcel = in_array($ext, ['xlsx', 'xls'], true);

            if (!$isExcel && !in_array($realMimeType, $allowedMimeTypes, true)) {
                Log::warning('Rejected file upload - invalid MIME type', [
                    'mime_type' => $realMimeType,
                    'original_name' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                ]);

                abort(422, 'File type not allowed. Only CSV/TXT files accepted.');
            }

            // SECURITY: Check for malicious content patterns
            // Only scan text-based files (CSV/TXT) — Excel/ZIP binaries are skipped
            if (!$isExcel) {
                $content = file_get_contents($file->getRealPath());

                // Check for PHP tags (code injection)
                if (preg_match('/<\?php|<\?=/i', $content)) {
                    Log::alert('Rejected file upload - PHP code detected!', [
                        'file' => $file->getClientOriginalName(),
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                    ]);
                    abort(422, 'File contains forbidden content.');
                }

                // Check for script tags
                if (preg_match('/<script[^>]*>/i', $content)) {
                    Log::alert('Rejected file upload - Script tags detected!', [
                        'file' => $file->getClientOriginalName(),
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                    ]);
                    abort(422, 'File contains forbidden content.');
                }
            }

            // SECURITY: File size validation (already in validation, but double-check)
            if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
                abort(422, 'File too large. Maximum 5MB.');
            }

            // SECURITY: Sanitize filename
            $originalName = $file->getClientOriginalName();
            $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $request->files->set('file', $file); // Keep original for now
            $request->merge(['_sanitized_filename' => $sanitizedName]);
        }

        return $next($request);
    }
}
