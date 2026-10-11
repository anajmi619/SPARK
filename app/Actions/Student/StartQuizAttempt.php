<?php

namespace App\Actions\Student;

use App\Enums\AttemptStatus;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Enums\UserStatus;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartQuizAttempt
{
    /**
     * Start the next quiz attempt or return the student's unfinished attempt.
     */
    public function handle(Topic $topic, User $student): QuizAttempt
    {
        if ($topic->status !== TopicStatus::Published) {
            abort(404);
        }

        return DB::transaction(function () use ($topic, $student): QuizAttempt {
            $lockedStudent = User::query()
                ->whereKey($student->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($lockedStudent->status === UserStatus::Active, 403);

            $existingAttempt = QuizAttempt::query()
                ->where('student_id', $student->user_id)
                ->where('topic_id', $topic->topic_id)
                ->where('status', AttemptStatus::InProgress->value)
                ->lockForUpdate()
                ->orderByDesc('attempt_number')
                ->first();

            if ($existingAttempt !== null) {
                return $existingAttempt;
            }

            $hasActiveQuestions = QuizQuestion::query()
                ->where('topic_id', $topic->topic_id)
                ->where('status', QuestionStatus::Active->value)
                ->exists();

            if (! $hasActiveQuestions) {
                throw ValidationException::withMessages([
                    'topic' => ['Tiada soalan kuiz aktif untuk topik ini.'],
                ]);
            }

            $nextAttemptNumber = (int) QuizAttempt::query()
                ->where('student_id', $student->user_id)
                ->where('topic_id', $topic->topic_id)
                ->max('attempt_number') + 1;

            return QuizAttempt::query()->create([
                'student_id' => $student->user_id,
                'topic_id' => $topic->topic_id,
                'attempt_number' => $nextAttemptNumber,
                'status' => AttemptStatus::InProgress,
                'started_at' => now(),
            ]);
        });
    }
}
