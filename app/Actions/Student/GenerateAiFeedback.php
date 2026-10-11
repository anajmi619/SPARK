<?php

namespace App\Actions\Student;

use App\Enums\AiFeedbackSource;
use App\Enums\AiFeedbackStatus;
use App\Enums\AnswerOption;
use App\Models\QuizQuestion;
use App\Services\AiFeedbackService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class GenerateAiFeedback
{
    public function __construct(private AiFeedbackService $feedback) {}

    /** @return array{status: AiFeedbackStatus, text: string|null, source: AiFeedbackSource|null} */
    public function handle(QuizQuestion $question, AnswerOption $selectedOption, int $studentId): array
    {
        if (! $this->feedback->canGenerate($question, $selectedOption)) {
            return $this->failed();
        }

        $cacheKey = $this->cacheKey($question, $selectedOption);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && filled($cached)) {
            return ['status' => AiFeedbackStatus::Generated, 'text' => $cached, 'source' => AiFeedbackSource::Cache];
        }

        try {
            return Cache::lock($cacheKey.':lock', 20)->block(3, function () use ($cacheKey, $question, $selectedOption, $studentId): array {
                $cached = Cache::get($cacheKey);

                if (is_string($cached) && filled($cached)) {
                    return ['status' => AiFeedbackStatus::Generated, 'text' => $cached, 'source' => AiFeedbackSource::Cache];
                }

                if ($this->limitsExceeded($studentId)) {
                    return $this->failed();
                }

                $this->consumeLimits($studentId);
                $text = $this->feedback->generate($question, $selectedOption);

                if (filled($text)) {
                    Cache::put($cacheKey, $text, now()->addDays(max(1, (int) config('quiz.ai_feedback_cache_days', 30))));

                    return ['status' => AiFeedbackStatus::Generated, 'text' => $text, 'source' => AiFeedbackSource::Api];
                }

                return ['status' => AiFeedbackStatus::Failed, 'text' => null, 'source' => AiFeedbackSource::Api];
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->failed();
        }
    }

    private function limitsExceeded(int $studentId): bool
    {
        return RateLimiter::tooManyAttempts($this->studentLimitKey($studentId), max(1, (int) config('quiz.ai_feedback_per_student_hourly_limit', 10)))
            || RateLimiter::tooManyAttempts($this->dailyLimitKey(), max(1, (int) config('quiz.ai_feedback_daily_limit', 100)));
    }

    private function consumeLimits(int $studentId): void
    {
        RateLimiter::hit($this->studentLimitKey($studentId), 3600);
        $now = now();
        $secondsUntilTomorrow = $now->copy()->endOfDay()->getTimestamp() - $now->getTimestamp() + 1;
        RateLimiter::hit($this->dailyLimitKey(), max(1, $secondsUntilTomorrow));
    }

    private function studentLimitKey(int $studentId): string
    {
        return 'quiz-ai-feedback:student:'.$studentId;
    }

    private function dailyLimitKey(): string
    {
        return 'quiz-ai-feedback:global:'.now()->toDateString();
    }

    private function cacheKey(QuizQuestion $question, AnswerOption $selectedOption): string
    {
        $payload = [
            'question_id' => $question->getKey(),
            'question' => $question->question_text,
            'options' => [$question->option_a, $question->option_b, $question->option_c, $question->option_d],
            'selected' => $selectedOption->value,
            'correct' => $question->correct_option->value,
            'explanation' => $question->explanation,
            'context' => $question->curriculum_context,
            'model' => config('services.gemini.model'),
        ];

        return 'quiz-ai-feedback:'.hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return array{status: AiFeedbackStatus, text: null, source: null} */
    private function failed(): array
    {
        return ['status' => AiFeedbackStatus::Failed, 'text' => null, 'source' => null];
    }
}
