<?php

namespace App\Actions\Teacher;

use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTopicModule
{
    use StoresTopicMedia;

    /**
     * Delete an unused module and close the resulting topic-order gap.
     */
    public function handle(Topic $topic): void
    {
        $mediaPaths = DB::transaction(function () use ($topic): array {
            $lockedTopic = Topic::query()
                ->whereKey($topic->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTopic->questions()->exists() || $lockedTopic->quizAttempts()->exists()) {
                throw ValidationException::withMessages([
                    'topic' => ['Modul yang mempunyai soalan kuiz atau rekod percubaan tidak boleh dipadamkan.'],
                ]);
            }

            $deletedTopicOrder = $lockedTopic->topic_order;
            $mediaPaths = [
                [$lockedTopic->image_path, 'public'],
                [$lockedTopic->pdf_path, 'local'],
            ];

            $lockedTopic->delete();

            Topic::query()
                ->where('topic_order', '>', $deletedTopicOrder)
                ->orderBy('topic_order')
                ->get()
                ->each(function (Topic $remainingTopic): void {
                    $remainingTopic->decrement('topic_order');
                });

            return $mediaPaths;
        });

        foreach ($mediaPaths as [$path, $disk]) {
            $this->deleteTopicMedia($path, $disk);
        }
    }
}
