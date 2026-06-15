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

           $realMimeType = $file->getMimeType();

            $allowedMimeTypes = [
                'text/plain',
                'text/csv',
                'application/csv',
                'text/x-csv',

                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'application/x-zip-compressed',
                'multipart/x-zip',

                'application/vnd.ms-excel',
                'application/CDFV2',
                'application/msword',
                'application/octet-stream',
            ];
            
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

            if (!$isExcel) {
                $content = file_get_contents($file->getRealPath());

                if (preg_match('/<\?php|<\?=/i', $content)) {
                    Log::alert('Rejected file upload - PHP code detected!', [
                        'file' => $file->getClientOriginalName(),
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                    ]);
                    abort(422, 'File contains forbidden content.');
                }

                if (preg_match('/<script[^>]*>/i', $content)) {
                    Log::alert('Rejected file upload - Script tags detected!', [
                        'file' => $file->getClientOriginalName(),
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                    ]);
                    abort(422, 'File contains forbidden content.');
                }
            }

            if ($file->getSize() > 10 * 1024 * 1024) {
                abort(422, 'File too large. Maximum 5MB.');
            }

            $originalName = $file->getClientOriginalName();
            $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $request->files->set('file', $file);
            $request->merge(['_sanitized_filename' => $sanitizedName]);
        }

        return $next($request);
    }
}
