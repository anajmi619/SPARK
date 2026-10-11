<?php

namespace App\Actions\Teacher;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

trait StoresTopicMedia
{
    /**
     * Store one media file and return its relative path.
     */
    protected function storeTopicMedia(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $path = $file->store($directory, $disk);

        if (! is_string($path)) {
            throw new RuntimeException('Media pembelajaran tidak dapat disimpan.');
        }

        return $path;
    }

    /**
     * Remove a stored topic media file when it is no longer referenced.
     */
    protected function deleteTopicMedia(?string $path, string $disk = 'public'): void
    {
        if ($path !== null) {
            Storage::disk($disk)->delete($path);
        }
    }
}
