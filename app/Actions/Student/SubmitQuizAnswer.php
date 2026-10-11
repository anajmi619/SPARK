<?php

namespace App\Actions\Student;

use App\Enums\AiFeedbackStatus;
use App\Enums\AnswerOption;
use App\Enums\AttemptStatus;
use App\Enums\QuestionStatus;
use App\Enums\UserStatus;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitQuizAnswer
{
    public function __construct(private CalculateStudentProgress $calculateStudentProgress) {}

    /**
     * Record a student answer, complete the attempt when appropriate, and generate feedback for an incorrect answer.
     *
     * @return array{answer: QuizAnswer, levelUp: Level|null}
     */
    public function handle(
        QuizAttempt $attempt,
        QuizQuestion $question,
        AnswerOption $selectedOption,
        GenerateAiFeedback $generateAiFeedback,
    ): array {
        $submission = DB::transaction(function () use ($attempt, $question, $selectedOption): array {
            // Serialize completions across this student's topics before updating their level.
            $student = User::query()->whereKey($attempt->student_id)->lockForUpdate()->firstOrFail();
            abort_unless($student->status === UserStatus::Active, 403);
            $lockedAttempt = QuizAttempt::query()
                ->whereKey($attempt->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->status !== AttemptStatus::InProgress) {
                throw ValidationException::withMessages([
                    'selected_option' => ['Percubaan kuiz ini telah selesai.'],
                ]);
            }

            $lockedQuestion = QuizQuestion::query()
                ->whereKey($question->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedQuestion->topic_id !== $lockedAttempt->topic_id || $lockedQuestion->status !== QuestionStatus::Active) {
                abort(404);
            }

            if ($lockedAttempt->answers()->where('question_id', $lockedQuestion->question_id)->exists()) {
                throw ValidationException::withMessages([
                    'selected_option' => ['Soalan ini telah dijawab.'],
                ]);
            }

            $isCorrect = $selectedOption === $lockedQuestion->correct_option;
            $options = [];
            foreach (['a', 'b', 'c', 'd'] as $option) {
                $options[strtoupper($option)] = [
                    'text' => $lockedQuestion->getAttribute('option_'.$option),
                    'image' => $lockedQuestion->getAttribute('option_'.$option.'_image_path'),
                ];
            }
            $answer = QuizAnswer::query()->create([
                'attempt_id' => $lockedAttempt->attempt_id,
                'question_id' => $lockedQuestion->question_id,
                'selected_option' => $selectedOption,
                'is_correct' => $isCorrect,
                'question_snapshot' => $lockedQuestion->question_text,
                'selected_answer_snapshot' => $this->optionText($lockedQuestion, $selectedOption),
                'correct_answer_snapshot' => $this->optionText($lockedQuestion, $lockedQuestion->correct_option),
                'explanation_snapshot' => $lockedQuestion->explanation,
                'presentation_snapshot' => [
                    'image' => $lockedQuestion->image_path,
                    'options' => $options,
                    'correct' => $lockedQuestion->correct_option->value,
                ],
                'ai_feedback_status' => $isCorrect ? AiFeedbackStatus::NotRequired : AiFeedbackStatus::Failed,
                'answered_at' => now(),
            ]);

            $hasRemainingQuestions = QuizQuestion::query()
                ->where('topic_id', $lockedAttempt->topic_id)
                ->where('status', QuestionStatus::Active->value)
                ->whereDoesntHave('answers', function ($query) use ($lockedAttempt): void {
                    $query->where('attempt_id', $lockedAttempt->attempt_id);
                })
                ->exists();

            $levelUp = null;
            if (! $hasRemainingQuestions) {
                $previousProgress = $this->calculateStudentProgress->handle($student);
                $lockedAttempt->update([
                    'score' => $lockedAttempt->answers()->where('is_correct', true)->count(),
                    'status' => AttemptStatus::Completed,
                    'completed_at' => now(),
                ]);

                $progress = $this->calculateStudentProgress->synchronize($student);
                if ($progress['currentLevel']->level_number > $previousProgress['currentLevel']->level_number) {
                    $levelUp = $progress['currentLevel'];
                }
            }

            return [
                'answer' => $answer,
                'question' => $lockedQuestion,
                'should_generate_feedback' => ! $isCorrect,
                'levelUp' => $levelUp,
            ];
        });

        if (! $submission['should_generate_feedback']) {
            return ['answer' => $submission['answer'], 'levelUp' => $submission['levelUp']];
        }

        $feedback = $generateAiFeedback->handle($submission['question'], $selectedOption, $attempt->student_id);

        $submission['answer']->update([
            'ai_feedback_status' => $feedback['status'],
            'ai_feedback_text' => $feedback['text'],
            'ai_feedback_source' => $feedback['source'],
        ]);

        return ['answer' => $submission['answer']->refresh(), 'levelUp' => $submission['levelUp']];
    }

    /**
     * Return answer text for an option stored on a quiz question.
     */
    private function optionText(QuizQuestion $question, AnswerOption $option): string
    {
        return match ($option) {
            AnswerOption::A => $question->option_a,
            AnswerOption::B => $question->option_b,
            AnswerOption::C => $question->option_c,
            AnswerOption::D => $question->option_d,
        };
    }
}
