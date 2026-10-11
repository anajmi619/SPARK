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
use Database\Seeders\LivingInteractionTopicSeeder;

beforeEach(function () {
    $this->seed(LevelSeeder::class);
});

test('living interaction seeder creates the published notes and ten active questions', function () {
    $this->seed(LivingInteractionTopicSeeder::class);

    $topic = Topic::query()->where('topic_order', 4)->sole();
    $questions = $topic->questions()->orderBy('question_order')->get();

    expect($topic->topic_name)->toBe('Interaksi Antara Hidupan')
        ->and($topic->status)->toBe(TopicStatus::Published)
        ->and($topic->content_text)->toContain('Interaksi antara hidupan ialah hubungan')
        ->and($questions)->toHaveCount(10)
        ->and($questions->whereNull('explanation'))->toBeEmpty()
        ->and($questions->pluck('question_order')->all())->toBe(range(1, 10))
        ->and($questions->pluck('status')->unique()->all())->toBe([QuestionStatus::Active])
        ->and($questions->pluck('correct_option')->all())->toBe([
            AnswerOption::B, AnswerOption::C, AnswerOption::A, AnswerOption::D, AnswerOption::B,
            AnswerOption::C, AnswerOption::A, AnswerOption::D, AnswerOption::B, AnswerOption::C,
        ]);
});

test('living interaction seeder is idempotent', function () {
    $this->seed(LivingInteractionTopicSeeder::class);
    $firstIds = QuizQuestion::query()->orderBy('question_order')->pluck('question_id')->all();
    $this->seed(LivingInteractionTopicSeeder::class);

    expect(Topic::query()->where('topic_order', 4)->count())->toBe(1)
        ->and(QuizQuestion::query()->count())->toBe(10)
        ->and(QuizQuestion::query()->orderBy('question_order')->pluck('question_id')->all())->toBe($firstIds);
});

test('students can start the ten-question living interaction quiz', function () {
    $this->seed(LivingInteractionTopicSeeder::class);
    $year = AcademicYear::factory()->active()->create();
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
    ]);
    $topic = Topic::query()->where('topic_order', 4)->sole();

    $this->actingAs($student)->get(route('student.learning.show', $topic))
        ->assertOk()
        ->assertSee('Interaksi Antara Hidupan')
        ->assertSee('Mula Kuiz');

    $this->post(route('student.quiz.start', $topic))->assertRedirect();
    $attempt = $student->quizAttempts()->sole();

    $this->get(route('student.quiz.show', $attempt))
        ->assertOk()
        ->assertSee('Apakah maksud interaksi antara hidupan?')
        ->assertSee('Soalan 1 daripada 10');
});
