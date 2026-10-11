<?php

use App\Enums\AiFeedbackSource;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\LevelSeeder;

beforeEach(function () {
    $this->seed(LevelSeeder::class);
    $this->year = AcademicYear::factory()->active()->create(['year' => 2026]);
    $this->firstClass = Kelas::factory()->create(['nama' => 'Kelas Amanah']);
    $this->secondClass = Kelas::factory()->create(['nama' => 'Kelas Bestari']);
});

test('teachers can manage learning but cannot open administrator modules', function () {
    $teacher = User::factory()->teacher()->create();
    $teacher->assignedClasses()->attach($this->firstClass);

    $this->actingAs($teacher)->get(route('teacher.topics.index'))->assertOk();
    $this->get(route('teacher.progress.index'))->assertOk();
    $this->get(route('teacher.users.index'))->assertForbidden();
    $this->get(route('teacher.kelas.index'))->assertForbidden();
    $this->get(route('teacher.academic-years.index'))->assertForbidden();
});

test('teacher reports contain only students from assigned classes', function () {
    $teacher = User::factory()->teacher()->create();
    $teacher->assignedClasses()->attach($this->firstClass);
    $level = Level::query()->where('level_number', 1)->sole();
    $assigned = User::factory()->student()->active()->create([
        'academic_year_id' => $this->year->academic_year_id,
        'current_level_id' => $level->level_id,
        'kelas_id' => $this->firstClass->id,
        'full_name' => 'Pelajar Kelas Amanah',
    ]);
    $other = User::factory()->student()->active()->create([
        'academic_year_id' => $this->year->academic_year_id,
        'current_level_id' => $level->level_id,
        'kelas_id' => $this->secondClass->id,
        'full_name' => 'Pelajar Kelas Bestari',
    ]);

    $this->actingAs($teacher)->get(route('teacher.progress.index'))
        ->assertOk()
        ->assertSee($assigned->full_name)
        ->assertDontSee($other->full_name);

    $this->get(route('teacher.progress.show', $other))->assertNotFound();
    $this->get(route('teacher.progress.index', ['kelas_id' => $this->secondClass->id]))->assertForbidden();
});

test('administrator modules require a recently confirmed password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('teacher.users.index'))
        ->assertRedirect(route('password.confirm'));

    $this->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get(route('teacher.users.index'))
        ->assertOk();
});

test('administrators can deactivate teachers and active sessions are revoked', function () {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create(['status' => UserStatus::Active]);

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->patch(route('teacher.users.status.update', $teacher), ['status' => UserStatus::Inactive->value])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    expect($teacher->refresh()->status)->toBe(UserStatus::Inactive);
});

test('an administrator cannot deactivate their own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->from(route('teacher.users.index'))
        ->patch(route('teacher.users.status.update', $admin), ['status' => UserStatus::Inactive->value])
        ->assertRedirect(route('teacher.users.index', absolute: false))
        ->assertSessionHasErrors('status');

    expect($admin->refresh()->status)->toBe(UserStatus::Active);
});

test('public recovery requests are rate limited by username and address', function () {
    foreach (range(1, 3) as $attempt) {
        $this->post(route('password.recovery.store'), ['username' => 'pelajar_uji'])
            ->assertRedirect(route('login', absolute: false));
    }

    $this->post(route('password.recovery.store'), ['username' => 'pelajar_uji'])
        ->assertTooManyRequests();
});

test('administrators can see daily Gemini API and cache usage', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $this->year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
        'kelas_id' => $this->firstClass->id,
    ]);
    $topic = Topic::factory()->create();
    $attempt = QuizAttempt::factory()->create(['student_id' => $student->user_id, 'topic_id' => $topic->topic_id]);
    $firstQuestion = QuizQuestion::factory()->create(['topic_id' => $topic->topic_id, 'question_order' => 1]);
    $secondQuestion = QuizQuestion::factory()->create(['topic_id' => $topic->topic_id, 'question_order' => 2]);
    QuizAnswer::factory()->incorrectWithFeedback()->create([
        'attempt_id' => $attempt->attempt_id,
        'question_id' => $firstQuestion->question_id,
        'ai_feedback_source' => AiFeedbackSource::Api,
        'answered_at' => now(),
    ]);
    QuizAnswer::factory()->incorrectWithFeedback()->create([
        'attempt_id' => $attempt->attempt_id,
        'question_id' => $secondQuestion->question_id,
        'ai_feedback_source' => AiFeedbackSource::Cache,
        'answered_at' => now(),
    ]);

    $this->actingAs($admin)->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee('Penggunaan Maklum Balas AI Hari Ini')
        ->assertViewHas('aiUsage', fn (array $usage): bool => $usage['apiCalls'] === 1
            && $usage['cacheHits'] === 1
            && $usage['dailyLimit'] === 100);
});
