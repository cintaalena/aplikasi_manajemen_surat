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

                // Excel
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                'application/vnd.ms-excel',                                          // .xls (common)
                'application/CDFV2',                                                 // .xls (OLE / Windows)
            ];
            
            if (!in_array($realMimeType, $allowedMimeTypes, true)) {
                Log::warning('Rejected file upload - invalid MIME type', [
                    'mime_type' => $realMimeType,
                    'original_name' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                ]);

                abort(422, 'File type not allowed. Only CSV/TXT files accepted.');
            }

            // SECURITY: Check for malicious content patterns
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

            // SECURITY: File size validation (already in validation, but double-check)
            if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
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
