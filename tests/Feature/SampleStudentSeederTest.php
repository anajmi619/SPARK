<?php

use App\Actions\Student\CalculateStudentProgress;
use App\Actions\Teacher\BuildProgressReports;
use App\Enums\AiFeedbackStatus;
use App\Enums\AttemptStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\KelasSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\SampleStudentSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config()->set('demo.sample_student_password', 'test-password-only');
});

test('sample student seeder creates a complete mixed-performance cohort without changing existing students', function () {
    $this->seed(DatabaseSeeder::class);
    $year = AcademicYear::query()->where('status', 'active')->sole();
    $existingStudent = User::factory()->student()->active()->create([
        'username' => 'pelajar_sedia_ada',
        'academic_year_id' => $year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
        'kelas_id' => Kelas::query()->where('nama', 'Ibnu Sina')->sole()->id,
    ]);
    $existingAttempt = QuizAttempt::factory()->completed(5)->create([
        'student_id' => $existingStudent->user_id,
        'topic_id' => 1,
    ]);

    $this->seed(SampleStudentSeeder::class);

    $demoStudents = User::query()
        ->where('username', 'like', 'pelajar_demo%')
        ->orderBy('username')
        ->get();
    $demoStudentIds = $demoStudents->modelKeys();
    $attempts = QuizAttempt::query()
        ->whereIn('student_id', $demoStudentIds)
        ->orderBy('attempt_id')
        ->withCount([
            'answers',
            'answers as correct_answers_count' => fn ($query) => $query->where('is_correct', true),
        ])
        ->get();

    expect($demoStudents)->toHaveCount(20)
        ->and($demoStudents->where('role', UserRole::Student)->count())->toBe(20)
        ->and($demoStudents->where('status', UserStatus::Active)->count())->toBe(20)
        ->and($demoStudents->where('academic_year_id', $year->academic_year_id)->count())->toBe(20)
        ->and($demoStudents->groupBy('kelas_id')->map->count()->sort()->values()->all())->toBe([4, 4, 4, 4, 4])
        ->and($demoStudents->every(fn (User $student): bool => Hash::check('test-password-only', $student->password_hash)))->toBeTrue()
        ->and($attempts)->toHaveCount(85)
        ->and($attempts->where('status', AttemptStatus::Completed))->toHaveCount(85)
        ->and($attempts->every(fn (QuizAttempt $attempt): bool => $attempt->answers_count === 10))->toBeTrue()
        ->and($attempts->every(fn (QuizAttempt $attempt): bool => $attempt->score === $attempt->correct_answers_count))->toBeTrue()
        ->and(QuizAnswer::query()->whereIn('attempt_id', $attempts->modelKeys())->count())->toBe(850)
        ->and(QuizAnswer::query()->whereIn('attempt_id', $attempts->modelKeys())->where('is_correct', false)->where('ai_feedback_status', '!=', AiFeedbackStatus::Failed->value)->doesntExist())->toBeTrue()
        ->and(QuizAnswer::query()->whereIn('attempt_id', $attempts->modelKeys())->whereNotNull('ai_feedback_source')->doesntExist())->toBeTrue()
        ->and($existingStudent->refresh()->username)->toBe('pelajar_sedia_ada')
        ->and($existingAttempt->refresh()->score)->toBe(5);

    $progress = app(CalculateStudentProgress::class);
    foreach ([
        'pelajar_demo01' => [380, 3, 4],
        'pelajar_demo02' => [300, 3, 4],
        'pelajar_demo03' => [170, 2, 3],
        'pelajar_demo04' => [60, 1, 2],
    ] as $username => [$points, $level, $completedTopics]) {
        $student = $demoStudents->firstWhere('username', $username);
        $studentProgress = $progress->handle($student);
        expect($studentProgress['totalPoints'])->toBe($points)
            ->and($studentProgress['currentLevel']->level_number)->toBe($level)
            ->and($studentProgress['completedTopics'])->toBe($completedTopics)
            ->and($student->refresh()->currentLevel->level_number)->toBe($level);
    }

    $reports = app(BuildProgressReports::class);
    $sampleStudents = $reports->students($year->academic_year_id, search: 'pelajar_demo');
    expect($reports->summary($sampleStudents))->toBe([
        'studentCount' => 20,
        'activeCount' => 20,
        'supportCount' => 10,
        'noResultCount' => 0,
        'averageScore' => 70.0,
        'completion' => 27.1,
    ]);

    $firstIds = [
        'users' => $demoStudents->modelKeys(),
        'attempts' => $attempts->modelKeys(),
        'answers' => QuizAnswer::query()->whereIn('attempt_id', $attempts->modelKeys())->orderBy('answer_id')->pluck('answer_id')->all(),
    ];
    $this->seed(SampleStudentSeeder::class);

    expect(User::query()->where('username', 'like', 'pelajar_demo%')->orderBy('username')->pluck('user_id')->all())->toBe($firstIds['users'])
        ->and(QuizAttempt::query()->whereIn('student_id', $demoStudentIds)->orderBy('attempt_id')->pluck('attempt_id')->all())->toBe($firstIds['attempts'])
        ->and(QuizAnswer::query()->whereIn('attempt_id', $attempts->modelKeys())->orderBy('answer_id')->pluck('answer_id')->all())->toBe($firstIds['answers']);
});

test('sample student seeder requires an active academic year', function () {
    $this->seed([LevelSeeder::class, KelasSeeder::class]);

    expect(fn () => $this->seed(SampleStudentSeeder::class))
        ->toThrow(RuntimeException::class, 'Tahun akademik aktif diperlukan');
});

test('sample student seeder requires all five classes', function () {
    $this->seed([AcademicYearSeeder::class, LevelSeeder::class]);
    Kelas::query()->where('nama', '!=', 'Ibnu Sina')->delete();

    expect(fn () => $this->seed(SampleStudentSeeder::class))
        ->toThrow(RuntimeException::class, 'Lima kelas SPARK mesti tersedia');
});

test('sample student seeder requires ten active questions in each of the first four topics', function () {
    $this->seed(DatabaseSeeder::class);
    QuizQuestion::query()
        ->whereHas('topic', fn ($query) => $query->where('topic_order', 4))
        ->firstOrFail()
        ->delete();

    expect(fn () => $this->seed(SampleStudentSeeder::class))
        ->toThrow(RuntimeException::class, 'Topik 1 hingga 4 mesti diterbitkan dan mempunyai tepat 10 soalan aktif');
});
