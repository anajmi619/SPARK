<?php

namespace App\Actions\Teacher;

use App\Enums\TopicStatus;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

class UpdateTopicContent
{
    use StoresTopicMedia;

    /**
     * Store learning content and media for a Science topic.
     */
    public function handle(
        Topic $topic,
        ?string $contentText,
        ?UploadedFile $pdfFile,
        ?string $youtubeUrl,
        bool $removePdf,
        TopicStatus $status,
        User $teacher,
    ): Topic {
        $storedMediaPaths = [];
        $mediaPathsToDelete = [];

        try {
            $pdfPath = $topic->pdf_path;
            if ($pdfFile !== null) {
                $pdfPath = $this->storeTopicMedia($pdfFile, 'topics/documents', 'local');
                $storedMediaPaths[] = [$pdfPath, 'local'];
                $mediaPathsToDelete[] = [$topic->pdf_path, 'local'];
            } elseif ($removePdf) {
                $pdfPath = null;
                $mediaPathsToDelete[] = [$topic->pdf_path, 'local'];
            }

            $topic->update([
                'content_text' => $contentText,
                'pdf_path' => $pdfPath,
                'youtube_url' => $youtubeUrl,
                'status' => $status,
                'created_by' => $topic->created_by ?? $teacher->user_id,
            ]);
        } catch (Throwable $exception) {
            foreach ($storedMediaPaths as [$path, $disk]) {
                $this->deleteTopicMedia($path, $disk);
            }

            throw $exception;
        }

        foreach ($mediaPathsToDelete as [$path, $disk]) {
            $this->deleteTopicMedia($path, $disk);
        }

        return $topic->refresh();
    }
}
