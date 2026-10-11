<?php

namespace App\Models;

use App\Enums\AiFeedbackSource;
use App\Enums\AiFeedbackStatus;
use App\Enums\AnswerOption;
use Carbon\CarbonImmutable;
use Database\Factories\QuizAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $answer_id
 * @property int $attempt_id
 * @property int $question_id
 * @property AnswerOption $selected_option
 * @property bool $is_correct
 * @property string $question_snapshot
 * @property string $selected_answer_snapshot
 * @property string $correct_answer_snapshot
 * @property string|null $ai_feedback_text
 * @property AiFeedbackStatus $ai_feedback_status
 * @property AiFeedbackSource|null $ai_feedback_source
 * @property CarbonImmutable $answered_at
 * @property string|null $explanation_snapshot
 * @property array{image: string|null, options: array<string, array{text: string, image: string|null}>, correct: string}|null $presentation_snapshot
 */
#[Fillable([
    'attempt_id',
    'question_id',
    'selected_option',
    'is_correct',
    'question_snapshot',
    'selected_answer_snapshot',
    'correct_answer_snapshot',
    'ai_feedback_text',
    'ai_feedback_status',
    'ai_feedback_source',
    'answered_at',
    'explanation_snapshot', 'presentation_snapshot',
])]
class QuizAnswer extends Model
{
    /** @use HasFactory<QuizAnswerFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $primaryKey = 'answer_id';

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id', 'attempt_id');
    }

    /**
     * @return BelongsTo<QuizQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id', 'question_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_option' => AnswerOption::class,
            'is_correct' => 'boolean',
            'ai_feedback_status' => AiFeedbackStatus::class,
            'ai_feedback_source' => AiFeedbackSource::class,
            'answered_at' => 'immutable_datetime',
            'presentation_snapshot' => 'array',
        ];
    }
}
