<?php

namespace App\Actions\Teacher;

use App\Models\QuizQuestion;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteQuizQuestion
{
    /**
     * Delete an unused question and close the resulting sequence gap.
     */
    public function handle(QuizQuestion $question): void
    {
        $paths = DB::transaction(function () use ($question): array {
            $lockedQuestion = QuizQuestion::query()
                ->whereKey($question->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Topic::query()
                ->whereKey($lockedQuestion->topic_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedQuestion->answers()->exists()) {
                throw ValidationException::withMessages([
                    'question' => ['Soalan yang mempunyai rekod jawapan tidak boleh dipadamkan. Nyahaktifkan soalan ini sebaliknya.'],
                ]);
            }

            $deletedQuestionOrder = $lockedQuestion->question_order;
            $topicId = $lockedQuestion->topic_id;
            $paths = [];
            foreach (QuizQuestion::imageFields() as $field) {
                $path = $lockedQuestion->getAttribute($field.'_path');
                if (is_string($path)) {
                    $paths[] = $path;
                }
            }

            $lockedQuestion->delete();

            QuizQuestion::query()
                ->where('topic_id', $topicId)
                ->where('question_order', '>', $deletedQuestionOrder)
                ->orderBy('question_order')
                ->get()
                ->each(function (QuizQuestion $remainingQuestion): void {
                    $remainingQuestion->decrement('question_order');
                });

            return $paths;
        });
        Storage::disk('public')->delete($paths);
    }
}
