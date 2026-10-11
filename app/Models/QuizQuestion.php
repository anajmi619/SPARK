<?php

namespace App\Models;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use Database\Factories\QuizQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $question_id
 * @property int $topic_id
 * @property int $question_order
 * @property string $question_text
 * @property string $option_a
 * @property string $option_b
 * @property string $option_c
 * @property string $option_d
 * @property AnswerOption $correct_option
 * @property string|null $curriculum_context
 * @property QuestionStatus $status
 * @property string|null $image_path
 * @property string|null $option_a_image_path
 * @property string|null $option_b_image_path
 * @property string|null $option_c_image_path
 * @property string|null $option_d_image_path
 * @property string|null $explanation
 */
#[Fillable([
    'topic_id',
    'question_order',
    'question_text',
    'option_a',
    'option_b',
    'option_c',
    'option_d',
    'correct_option',
    'curriculum_context',
    'status',
    'image_path', 'option_a_image_path', 'option_b_image_path', 'option_c_image_path', 'option_d_image_path', 'explanation',
])]
class QuizQuestion extends Model
{
    /** @use HasFactory<QuizQuestionFactory> */
    use HasFactory;

    protected $primaryKey = 'question_id';

    protected $hidden = ['correct_option', 'explanation', 'curriculum_context'];

    /** @return list<string> */
    public static function imageFields(): array
    {
        return ['image', 'option_a_image', 'option_b_image', 'option_c_image', 'option_d_image'];
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'topic_id');
    }

    /**
     * @return HasMany<QuizAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'question_id', 'question_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_order' => 'integer',
            'correct_option' => AnswerOption::class,
            'status' => QuestionStatus::class,
        ];
    }
}
