<?php

use App\Actions\Student\CalculateStudentProgress;
use App\Actions\Student\GenerateAiFeedback;
use App\Actions\Student\SubmitQuizAnswer;
use App\Enums\AnswerOption;
use App\Enums\AttemptStatus;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\LevelSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(LevelSeeder::class);
    config(['quiz.ai_feedback_enabled' => false]);
    Http::preventStrayRequests();
    $this->student = User::factory()->student()->active()->create([
        'academic_year_id' => AcademicYear::factory()->active(),
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
    ]);
});

function progressTopic(int $order): Topic
{
    return Topic::factory()->create([
        'topic_order' => $order,
        'topic_name' => "Topik Sains {$order}",
        'status' => TopicStatus::Published,
    ]);
}

function progressAttempt(User $student, Topic $topic, int $score, int $number = 1): QuizAttempt
{
    return QuizAttempt::factory()->completed($score)->create([
        'student_id' => $student->user_id,
        'topic_id' => $topic->topic_id,
        'attempt_number' => $number,
    ]);
}

function progressQuestion(Topic $topic, int $order = 1): QuizQuestion
{
    return QuizQuestion::factory()->create([
        'topic_id' => $topic->topic_id,
        'question_order' => $order,
        'correct_option' => AnswerOption::A,
        'status' => QuestionStatus::Active,
    ]);
}

test('a new student has zero points and a starting badge', function () {
    $progress = app(CalculateStudentProgress::class)->handle($this->student);

    expect($progress['totalPoints'])->toBe(0)
        ->and($progress['topicScores'])->toBe([])
        ->and($progress['completedTopics'])->toBe(0)
        ->and($progress['currentLevel']->level_name)->toBe('Permulaan')
        ->and($progress['nextLevel']->level_number)->toBe(2)
        ->and($progress['pointsToNextLevel'])->toBe(150)
        ->and($progress['levelProgress'])->toBe(0);

    $this->actingAs($this->student)->get(route('student.dashboard'))
        ->assertOk()->assertSee('Permulaan')
        ->assertSee('images/badges/level-1.png', false)
        ->assertSee('Lencana Tahap 1: Permulaan')
        ->assertSee('150 mata lagi untuk mencapai Tahap 2: Peneroka Sains.')
        ->assertSee('Tiada modul pembelajaran diterbitkan');
});

test('every configured level has a display badge asset', function () {
    foreach (range(1, 7) as $levelNumber) {
        expect(file_exists(public_path("images/badges/level-{$levelNumber}.png")))
            ->toBeTrue("Lencana Tahap {$levelNumber} tidak ditemui.");
    }
});

test('unlimited retakes can reach full topic points without duplicate or reduced points', function () {
    $topic = progressTopic(1);
    $calculator = app(CalculateStudentProgress::class);

    foreach ([6, 8, 7, 8, 10, 10] as $index => $score) {
        progressAttempt($this->student, $topic, $score, $index + 1);
        $progress = $calculator->synchronize($this->student);

        expect($progress['totalPoints'])->toBe([60, 80, 80, 80, 100, 100][$index])
            ->and($progress['completedTopics'])->toBe(1);
    }

    expect(QuizAttempt::query()->count())->toBe(6)
        ->and($progress['topicScores'][$topic->topic_id])->toBe(10);
});

test('points exclude unfinished attempts and other students but preserve archived topic results', function () {
    $topic = progressTopic(1);
    $topic->update(['status' => TopicStatus::Archived]);
    progressAttempt($this->student, $topic, 4);
    $unfinished = progressTopic(2);
    QuizAttempt::factory()->create([
        'student_id' => $this->student->user_id, 'topic_id' => $unfinished->topic_id,
        'score' => 10,
    ]);
    $otherStudent = User::factory()->student()->active()->create([
        'academic_year_id' => $this->student->academic_year_id,
        'current_level_id' => $this->student->current_level_id,
    ]);
    progressAttempt($otherStudent, $topic, 10);

    $progress = app(CalculateStudentProgress::class)->handle($this->student);
    expect($progress['totalPoints'])->toBe(40)
        ->and($progress['topicScores'])->toBe([$topic->topic_id => 4])
        ->and($progress['completedTopics'])->toBe(1);

    $this->actingAs($this->student)->get(route('student.dashboard'))
        ->assertOk()->assertDontSee($topic->topic_name)
        ->assertSee('Belum Menjawab Kuiz');
});

test('each level boundary uses the configured thresholds', function (int $points, int $level, int $remaining) {
    $scoreRemaining = intdiv($points, 10);
    for ($order = 1; $scoreRemaining > 0; $order++) {
        $score = min(10, $scoreRemaining);
        progressAttempt($this->student, progressTopic($order), $score);
        $scoreRemaining -= $score;
    }

    $progress = app(CalculateStudentProgress::class)->synchronize($this->student);
    expect($progress['totalPoints'])->toBe($points)
        ->and($progress['currentLevel']->level_number)->toBe($level)
        ->and($progress['pointsToNextLevel'])->toBe($remaining)
        ->and($progress['levelProgress'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
        ->and($this->student->refresh()->current_level_id)->toBe($progress['currentLevel']->level_id);

    if ($level === 7) {
        expect($progress['nextLevel'])->toBeNull()->and($progress['levelProgress'])->toBe(100);
        $response = $this->actingAs($this->student)->get(route('student.dashboard'))
            ->assertOk()->assertSee('Tahniah! Tahap tertinggi telah dicapai.')
            ->assertDontSee('mata lagi untuk mencapai Tahap');
        $response->assertSee($points === 1200 ? 'Mata maksimum telah dicapai.' : '100 mata lagi untuk mencapai 1,200 mata.');
    }
})->with([
    [0, 1, 150], [140, 1, 10], [150, 2, 150], [290, 2, 10],
    [300, 3, 200], [490, 3, 10], [500, 4, 200], [690, 4, 10],
    [700, 5, 200], [890, 5, 10], [900, 6, 200], [1090, 6, 10],
    [1100, 7, 0], [1200, 7, 0],
]);

test('quiz completion awards server calculated points and shows level up only once', function () {
    progressAttempt($this->student, progressTopic(1), 10);
    progressAttempt($this->student, progressTopic(2), 4);
    $topic = progressTopic(3);
    $question = progressQuestion($topic);
    $this->actingAs($this->student)->post(route('student.quiz.start', $topic))->assertRedirect();
    $attempt = QuizAttempt::query()->where('topic_id', $topic->topic_id)->sole();

    expect(app(CalculateStudentProgress::class)->handle($this->student)['totalPoints'])->toBe(140);

    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), [
        'selected_option' => 'A', 'score' => 10, 'points' => 1200, 'current_level_id' => 7,
    ])->assertRedirect()->assertSessionHas('quiz_level_up.number', 2);

    expect($attempt->refresh()->score)->toBe(1)
        ->and($this->student->refresh()->currentLevel->level_number)->toBe(2)
        ->and(app(CalculateStudentProgress::class)->handle($this->student)['totalPoints'])->toBe(150);

    $answer = $attempt->answers()->sole();
    $this->get(route('student.quiz.feedback', ['attempt' => $attempt, 'answer' => $answer]))->assertOk();
    $this->get(route('student.quiz.result', $attempt))->assertOk()
        ->assertSee('Tahniah, anda naik tahap! Tahap 2: Peneroka Sains.')
        ->assertSee('Lencana Baharu Diperoleh')
        ->assertSee('images/badges/level-2.png', false)
        ->assertViewHas('bestTopicScore', 1)
        ->assertViewHas('progress', fn (array $progress): bool => $progress['totalPoints'] === 150);
    $this->get(route('student.quiz.result', $attempt))->assertOk()
        ->assertDontSee('Tahniah, anda naik tahap!');

    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'A'])
        ->assertNotFound();
    expect($attempt->answers()->count())->toBe(1)
        ->and(app(CalculateStudentProgress::class)->handle($this->student)['totalPoints'])->toBe(150);
    Http::assertNothingSent();
});

test('short quizzes award ten points per correct answer and no points before completion', function () {
    $topic = progressTopic(1);
    $questions = collect(range(1, 3))->map(fn (int $order): QuizQuestion => progressQuestion($topic, $order));
    $this->actingAs($this->student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->sole();

    foreach ($questions as $index => $question) {
        $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'A'])
            ->assertRedirect();
        expect(app(CalculateStudentProgress::class)->handle($this->student)['totalPoints'])->toBe($index === 2 ? 30 : 0);
    }

    $this->get(route('student.quiz.result', $attempt))->assertOk()
        ->assertSee('daripada 3')->assertViewHas('bestTopicScore', 3);
    $this->get(route('student.dashboard'))->assertOk()->assertSee('Skor Terbaik: 3 / 10');
});

test('a lower completed retake does not remove the current badge or topic points', function () {
    progressAttempt($this->student, progressTopic(1), 10);
    $topic = progressTopic(2);
    progressAttempt($this->student, $topic, 5);
    app(CalculateStudentProgress::class)->synchronize($this->student);
    $question = progressQuestion($topic);
    $this->actingAs($this->student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->where('topic_id', $topic->topic_id)->where('attempt_number', 2)->sole();

    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'B'])
        ->assertRedirect()->assertSessionMissing('quiz_level_up');

    expect($attempt->refresh()->score)->toBe(0)
        ->and($this->student->refresh()->currentLevel->level_number)->toBe(2);
    $this->get(route('student.quiz.result', $attempt))->assertOk()
        ->assertViewHas('bestTopicScore', 5)
        ->assertViewHas('progress', fn (array $progress): bool => $progress['totalPoints'] === 150);
});

test('result summaries enforce ownership and redirect unfinished attempts', function () {
    $topic = progressTopic(1);
    $attempt = QuizAttempt::factory()->create(['student_id' => $this->student->user_id, 'topic_id' => $topic->topic_id]);
    $this->actingAs($this->student)->get(route('student.quiz.result', $attempt))
        ->assertRedirect(route('student.quiz.show', $attempt, absolute: false));
    $otherStudent = User::factory()->student()->active()->create([
        'academic_year_id' => $this->student->academic_year_id,
        'current_level_id' => $this->student->current_level_id,
    ]);
    $this->actingAs($otherStudent)->get(route('student.quiz.result', $attempt))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('student.quiz.result', $attempt))->assertForbidden();
});

test('level synchronization repairs historical levels and is idempotent without touching teachers', function () {
    progressAttempt($this->student, progressTopic(1), 10);
    progressAttempt($this->student, progressTopic(2), 5);
    $teacher = User::factory()->create();
    $beforeStatus = $this->student->status;

    $this->artisan('spark:sync-levels')->expectsOutputToContain('Tahap 1 pelajar telah diselaraskan.')->assertSuccessful();
    $attributes = $this->student->refresh()->getAttributes();
    $this->travel(1)->minutes();
    $this->artisan('spark:sync-levels')->assertSuccessful();

    expect($this->student->refresh()->getAttributes())->toBe($attributes)
        ->and($this->student->currentLevel->level_number)->toBe(2)
        ->and($this->student->status)->toBe($beforeStatus)
        ->and($teacher->refresh()->current_level_id)->toBeNull()
        ->and(QuizAttempt::query()->count())->toBe(2);
});

test('a level update failure rolls back the final answer and completion together', function () {
    $topic = progressTopic(1);
    $question = progressQuestion($topic);
    $attempt = QuizAttempt::factory()->create(['student_id' => $this->student->user_id, 'topic_id' => $topic->topic_id]);
    $calculator = Mockery::mock(CalculateStudentProgress::class)->makePartial();
    $calculator->shouldReceive('synchronize')->once()->andThrow(new RuntimeException('Level update failed'));
    $action = new SubmitQuizAnswer($calculator);

    expect(fn () => $action->handle($attempt, $question, AnswerOption::A, app(GenerateAiFeedback::class)))
        ->toThrow(RuntimeException::class, 'Level update failed');

    expect($attempt->refresh()->status)->toBe(AttemptStatus::InProgress)
        ->and($attempt->score)->toBeNull()
        ->and(QuizAnswer::query()->count())->toBe(0)
        ->and($this->student->refresh()->currentLevel->level_number)->toBe(1);
});
