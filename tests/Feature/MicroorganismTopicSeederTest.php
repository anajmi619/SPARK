<?php

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\LevelSeeder;
use Database\Seeders\MicroorganismTopicSeeder;

beforeEach(function () {
    $this->seed(LevelSeeder::class);
});

test('microorganism seeder creates the published notes and ten active questions', function () {
    $this->seed(MicroorganismTopicSeeder::class);

    $topic = Topic::query()->where('topic_order', 3)->sole();
    $questions = $topic->questions()->orderBy('question_order')->get();

    expect($topic->topic_name)->toBe('Mikroorganisma')
        ->and($topic->status)->toBe(TopicStatus::Published)
        ->and($topic->content_text)->toContain('Mikroorganisma ialah hidupan seni')
        ->and($questions)->toHaveCount(10)
        ->and($questions->whereNull('explanation'))->toBeEmpty()
        ->and($questions->pluck('question_order')->all())->toBe(range(1, 10))
        ->and($questions->pluck('status')->unique()->all())->toBe([QuestionStatus::Active])
        ->and($questions->pluck('correct_option')->all())->toBe([
            AnswerOption::B, AnswerOption::C, AnswerOption::A, AnswerOption::D, AnswerOption::C,
            AnswerOption::B, AnswerOption::A, AnswerOption::C, AnswerOption::B, AnswerOption::D,
        ]);
});

test('microorganism seeder is idempotent', function () {
    $this->seed(MicroorganismTopicSeeder::class);
    $firstIds = QuizQuestion::query()->orderBy('question_order')->pluck('question_id')->all();
    $this->seed(MicroorganismTopicSeeder::class);

    expect(Topic::query()->where('topic_order', 3)->count())->toBe(1)
        ->and(QuizQuestion::query()->count())->toBe(10)
        ->and(QuizQuestion::query()->orderBy('question_order')->pluck('question_id')->all())->toBe($firstIds);
});

test('students can start the ten-question microorganism quiz', function () {
    $this->seed(MicroorganismTopicSeeder::class);
    $year = AcademicYear::factory()->active()->create();
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
    ]);
    $topic = Topic::query()->where('topic_order', 3)->sole();

    $this->actingAs($student)->get(route('student.learning.show', $topic))
        ->assertOk()
        ->assertSee('Mikroorganisma')
        ->assertSee('Mula Kuiz');

    $this->post(route('student.quiz.start', $topic))->assertRedirect();
    $attempt = $student->quizAttempts()->sole();

    $this->get(route('student.quiz.show', $attempt))
        ->assertOk()
        ->assertSee('Apakah maksud mikroorganisma?')
        ->assertSee('Soalan 1 daripada 10');
});
