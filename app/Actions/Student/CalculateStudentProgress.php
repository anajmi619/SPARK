<?php

namespace App\Actions\Student;

use App\Enums\AttemptStatus;
use App\Enums\UserRole;
use App\Models\Level;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-type StudentProgress array{topicScores: array<int, int>, totalPoints: int, currentLevel: Level, nextLevel: Level|null, pointsToNextLevel: int, levelProgress: int, completedTopics: int}
 */
class CalculateStudentProgress
{
    public const QUESTIONS_PER_TOPIC = 10;

    public const POINTS_PER_CORRECT = 10;

    public const TOTAL_TOPICS = 12;

    public const MAX_TOPIC_POINTS = self::QUESTIONS_PER_TOPIC * self::POINTS_PER_CORRECT;

    public const MAX_POINTS = self::TOTAL_TOPICS * self::MAX_TOPIC_POINTS;

    /** @return StudentProgress */
    public function handle(User $student): array
    {
        abort_unless($student->role === UserRole::Student, 403);

        // Archived topics still contribute earned points; only completed attempts count.
        $bestScores = QuizAttempt::query()
            ->where('student_id', $student->user_id)
            ->where('status', AttemptStatus::Completed->value)
            ->whereNotNull('score')
            ->selectRaw('topic_id, MAX(score) AS best_score')
            ->groupBy('topic_id')
            ->toBase()
            ->pluck('best_score', 'topic_id');

        $topicScores = [];
        foreach ($bestScores as $topicId => $score) {
            $topicScores[(int) $topicId] = max(0, min(self::QUESTIONS_PER_TOPIC, (int) $score));
        }

        $totalPoints = min(self::MAX_POINTS, array_sum($topicScores) * self::POINTS_PER_CORRECT);
        $currentLevel = Level::query()
            ->where('minimum_points', '<=', $totalPoints)
            ->where('maximum_points', '>=', $totalPoints)
            ->orderByDesc('minimum_points')
            ->firstOrFail();
        $nextLevel = Level::query()
            ->where('minimum_points', '>', $currentLevel->minimum_points)
            ->orderBy('minimum_points')
            ->first();

        $levelProgress = $nextLevel === null
            ? 100
            : (int) floor(100 * ($totalPoints - $currentLevel->minimum_points)
                / ($nextLevel->minimum_points - $currentLevel->minimum_points));

        return [
            'topicScores' => $topicScores,
            'totalPoints' => $totalPoints,
            'currentLevel' => $currentLevel,
            'nextLevel' => $nextLevel,
            'pointsToNextLevel' => $nextLevel === null ? 0 : $nextLevel->minimum_points - $totalPoints,
            'levelProgress' => max(0, min(100, $levelProgress)),
            'completedTopics' => count($topicScores),
        ];
    }

    /**
     * Keep the cached level consistent with completed quiz records.
     *
     * @return StudentProgress
     */
    public function synchronize(User $student): array
    {
        return DB::transaction(function () use ($student): array {
            $lockedStudent = User::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $progress = $this->handle($lockedStudent);

            if ($lockedStudent->current_level_id !== $progress['currentLevel']->level_id) {
                $lockedStudent->update(['current_level_id' => $progress['currentLevel']->level_id]);
            }

            return $progress;
        });
    }
}
