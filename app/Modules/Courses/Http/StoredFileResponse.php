<?php

namespace App\Modules\Courses\Http;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;

class StoredFileResponse
{
    /**
     * Streams a stored file. Local files use a file response, which supports
     * HTTP range requests (needed for seeking in audio, especially on Safari).
     */
    public static function make(string $disk, string $path, ?string $mimeType): Response
    {
        $filesystem = Storage::disk($disk);

        abort_unless($filesystem->exists($path), 404);

        $headers = [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($filesystem->getAdapter() instanceof LocalFilesystemAdapter) {
            return response()->file($filesystem->path($path), $headers);
        }

        return $filesystem->response($path, null, $headers);
    }
}
