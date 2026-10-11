<?php

namespace App\Actions\Teacher;

use App\Enums\TopicStatus;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateTopicModule
{
    use StoresTopicMedia;

    /**
     * Create the next module in the twelve-topic Science sequence.
     */
    public function handle(
        string $topicName,
        ?string $contentText,
        ?UploadedFile $pdfFile,
        ?string $youtubeUrl,
        TopicStatus $status,
        User $teacher,
    ): Topic {
        $pdfPath = null;

        try {
            $pdfPath = $pdfFile === null ? null : $this->storeTopicMedia($pdfFile, 'topics/documents', 'local');

            return DB::transaction(function () use ($topicName, $contentText, $pdfPath, $youtubeUrl, $status, $teacher): Topic {
                $topics = Topic::query()
                    ->lockForUpdate()
                    ->orderBy('topic_order')
                    ->get();

                if ($topics->count() >= 12) {
                    throw ValidationException::withMessages([
                        'topic_name' => ['Maksimum 12 modul pembelajaran telah ditetapkan.'],
                    ]);
                }

                $nextTopicOrder = $topics->isEmpty()
                    ? 1
                    : $topics->last()->topic_order + 1;

                return Topic::query()->create([
                    'topic_order' => $nextTopicOrder,
                    'topic_name' => $topicName,
                    'content_text' => $contentText,
                    'pdf_path' => $pdfPath,
                    'youtube_url' => $youtubeUrl,
                    'status' => $status,
                    'created_by' => $teacher->user_id,
                ]);
            });
        } catch (Throwable $exception) {
            $this->deleteTopicMedia($pdfPath, 'local');

            throw $exception;
        }
    }
}
