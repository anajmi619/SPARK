<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @property int $user_id
 * @property int|null $academic_year_id
 * @property int|null $current_level_id
 * @property string $full_name
 * @property string $username
 * @property string $password_hash
 * @property UserRole $role
 * @property int|null $kelas_id
 * @property-read Kelas|null $kelas
 * @property-read Collection<int, Kelas> $assignedClasses
 * @property UserStatus $status
 * @property-read AcademicYear|null $academicYear
 * @property-read Level|null $currentLevel
 */
#[Fillable([
    'academic_year_id',
    'current_level_id',
    'full_name',
    'username',
    'password_hash',
    'role',
    'kelas_id',
    'status',
    'recovery_requested_at',
])]
#[Hidden(['password_hash', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $authPasswordName = 'password_hash';

    /** @return BelongsTo<Kelas, $this> */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /** @return BelongsToMany<Kelas, $this> */
    public function assignedClasses(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_teacher', 'teacher_id', 'kelas_id')->withTimestamps();
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'academic_year_id');
    }

    /**
     * @return BelongsTo<Level, $this>
     */
    public function currentLevel(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'current_level_id', 'level_id');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id', 'user_id');
    }

    /**
     * @return HasMany<Topic, $this>
     */
    public function createdTopics(): HasMany
    {
        return $this->hasMany(Topic::class, 'created_by', 'user_id');
    }

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'password_hash' => 'hashed',
            'recovery_requested_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->full_name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }
}
