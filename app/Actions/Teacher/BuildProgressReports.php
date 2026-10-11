<?php

namespace App\Actions\Teacher;

use App\Actions\Student\CalculateStudentProgress;
use App\Enums\AttemptStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Kelas;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class BuildProgressReports
{
    public const SUPPORT_THRESHOLD = 60;

    /**
     * @param  array<int, int>|null  $allowedClassIds
     * @return EloquentBuilder<User>
     */
    public function students(?int $yearId, ?int $kelasId = null, ?string $search = null, ?array $allowedClassIds = null): EloquentBuilder
    {
        return User::query()
            ->where('users.role', UserRole::Student->value)
            ->whereIn('users.status', [UserStatus::Active->value, UserStatus::Inactive->value])
            ->where('users.academic_year_id', $yearId ?? 0)
            ->when($allowedClassIds !== null, fn ($query) => $query->whereIn('users.kelas_id', $allowedClassIds))
            ->when($kelasId !== null, fn ($query) => $query->where('users.kelas_id', $kelasId))
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('users.full_name', 'like', '%'.$search.'%')
                        ->orWhere('users.username', 'like', '%'.$search.'%');
                });
            });
    }

    /** @param EloquentBuilder<User> $students */
    public function bestScores(EloquentBuilder $students): Builder
    {
        // One contribution per student/topic, regardless of the number of retakes.
        return QuizAttempt::query()
            ->whereIn('student_id', (clone $students)->select('users.user_id'))
            ->where('status', AttemptStatus::Completed->value)
            ->whereNotNull('score')
            ->selectRaw('student_id, topic_id, MAX(score) AS best_score, COUNT(*) AS attempt_count, MAX(completed_at) AS last_completed_at')
            ->groupBy('student_id', 'topic_id')->toBase();
    }

    /** @param EloquentBuilder<User> $students */
    public function studentRows(EloquentBuilder $students, bool $supportOnly = false): Builder
    {
        $totals = DB::query()->fromSub($this->bestScores($students), 'best')
            ->select('student_id')
            ->selectRaw('AVG(best_score) * ? AS average_score, SUM(best_score) * ? AS total_points, COUNT(*) AS completed_topics', [
                100 / CalculateStudentProgress::QUESTIONS_PER_TOPIC,
                CalculateStudentProgress::POINTS_PER_CORRECT,
            ])
            ->selectRaw('SUM(CASE WHEN best_score * 100 < ? THEN 1 ELSE 0 END) AS weak_topics', [
                self::SUPPORT_THRESHOLD * CalculateStudentProgress::QUESTIONS_PER_TOPIC,
            ])->groupBy('student_id');

        return (clone $students)->toBase()
            ->leftJoinSub($totals, 'scores', 'scores.student_id', '=', 'users.user_id')
            ->leftJoin((new Kelas)->getTable().' as kelas', 'kelas.id', '=', 'users.kelas_id')
            ->select('users.user_id', 'users.full_name', 'users.username', 'kelas.nama as kelas_nama', 'users.status', 'users.academic_year_id', 'scores.average_score')
            ->selectRaw('COALESCE(scores.total_points, 0) AS total_points, COALESCE(scores.completed_topics, 0) AS completed_topics, COALESCE(scores.weak_topics, 0) AS weak_topics')
            ->when($supportOnly, fn (Builder $query) => $query->where('scores.weak_topics', '>', 0));
    }

    /**
     * @param  EloquentBuilder<User>  $students
     * @return array{studentCount: int, activeCount: int, supportCount: int, noResultCount: int, averageScore: float|null, completion: float}
     */
    public function summary(EloquentBuilder $students): array
    {
        $totals = DB::query()->fromSub($this->studentRows($students), 'students')
            ->selectRaw('COUNT(*) AS student_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS active_count', [UserStatus::Active->value])
            ->selectRaw('SUM(CASE WHEN weak_topics > 0 THEN 1 ELSE 0 END) AS support_count, SUM(CASE WHEN completed_topics = 0 THEN 1 ELSE 0 END) AS no_result_count, SUM(completed_topics) AS completed_count')
            ->first();
        $average = DB::query()->fromSub($this->bestScores($students), 'best')->avg('best_score');
        $count = (int) ($totals->student_count ?? 0);

        return [
            'studentCount' => $count,
            'activeCount' => (int) ($totals->active_count ?? 0),
            'supportCount' => (int) ($totals->support_count ?? 0),
            'noResultCount' => (int) ($totals->no_result_count ?? 0),
            'averageScore' => $average === null ? null : round((float) $average * 100 / CalculateStudentProgress::QUESTIONS_PER_TOPIC, 1),
            'completion' => $count === 0 ? 0.0 : round((int) ($totals->completed_count ?? 0) / ($count * CalculateStudentProgress::TOTAL_TOPICS) * 100, 1),
        ];
    }

    /**
     * @param  EloquentBuilder<User>  $students
     * @return Collection<int, stdClass>
     */
    public function topicRows(EloquentBuilder $students): Collection
    {
        $totals = DB::query()->fromSub($this->bestScores($students), 'best')
            ->select('topic_id')
            ->selectRaw('AVG(best_score) * ? AS average_score, COUNT(*) AS completed_students, SUM(attempt_count) AS attempt_count, MAX(last_completed_at) AS last_completed_at', [100 / CalculateStudentProgress::QUESTIONS_PER_TOPIC])
            ->selectRaw('SUM(CASE WHEN best_score * 100 < ? THEN 1 ELSE 0 END) AS weak_students', [self::SUPPORT_THRESHOLD * CalculateStudentProgress::QUESTIONS_PER_TOPIC])
            ->groupBy('topic_id');

        return Topic::query()->toBase()
            ->leftJoinSub($totals, 'scores', 'scores.topic_id', '=', 'topics.topic_id')
            ->select('topics.topic_id', 'topics.topic_order', 'topics.topic_name', 'topics.status', 'scores.average_score', 'scores.last_completed_at')
            ->selectRaw('COALESCE(scores.completed_students, 0) AS completed_students, COALESCE(scores.weak_students, 0) AS weak_students, COALESCE(scores.attempt_count, 0) AS attempt_count')
            ->orderBy('topics.topic_order')->get();
    }
}
