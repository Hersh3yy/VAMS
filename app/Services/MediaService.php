<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    /**
     * Store an uploaded file
     */
    public function storeFile(UploadedFile $file, string $directory = 'media'): array
    {
        $path = $file->store($directory, 'public');

        return [
            'path' => Storage::url($path),
            'type' => $this->getFileType($file),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(string $path): bool
    {
        $relativePath = str_replace('/storage/', '', parse_url($path, PHP_URL_PATH));

        return Storage::disk('public')->delete($relativePath);
    }

    /**
     * Get file type based on mime type
     */
    private function getFileType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        return 'file';
    }
}
