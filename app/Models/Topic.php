<?php

namespace App\Models;

use App\Enums\TopicStatus;
use App\Support\YouTubeUrl;
use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $topic_id
 * @property int $topic_order
 * @property string $topic_name
 * @property string|null $content_text
 * @property string|null $image_path
 * @property string|null $pdf_path
 * @property string|null $youtube_url
 * @property TopicStatus $status
 * @property int|null $created_by
 */
#[Fillable([
    'topic_order',
    'topic_name',
    'content_text',
    'image_path',
    'pdf_path',
    'youtube_url',
    'status',
    'created_by',
])]
class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory;

    protected $primaryKey = 'topic_id';

    public function youtubeEmbedUrl(): ?string
    {
        $id = YouTubeUrl::videoId($this->youtube_url);

        return $id === null ? null : 'https://www.youtube-nocookie.com/embed/'.$id;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'topic_id', 'topic_id');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'topic_id', 'topic_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'topic_order' => 'integer',
            'status' => TopicStatus::class,
        ];
    }
}
