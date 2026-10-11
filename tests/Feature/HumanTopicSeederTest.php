<?php

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\HumanTopicSeeder;
use Database\Seeders\LevelSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(LevelSeeder::class);
});

test('human topic seeder creates the published notes and ten active questions', function () {
    $this->seed(HumanTopicSeeder::class);

    $topic = Topic::query()->where('topic_order', 2)->sole();
    $questions = $topic->questions()->orderBy('question_order')->get();

    expect($topic->topic_name)->toBe('Manusia')
        ->and($topic->status)->toBe(TopicStatus::Published)
        ->and($topic->image_path)->toBe('topics/images/sains-tahun-6-unit-2.jpg')
        ->and($topic->content_text)->toContain('Sistem saraf manusia')
        ->and($questions)->toHaveCount(10)
        ->and($questions->whereNull('explanation'))->toBeEmpty()
        ->and($questions->pluck('question_order')->all())->toBe(range(1, 10))
        ->and($questions->pluck('status')->unique()->all())->toBe([QuestionStatus::Active])
        ->and($questions->pluck('correct_option')->all())->toBe([
            AnswerOption::C, AnswerOption::A, AnswerOption::B, AnswerOption::D, AnswerOption::C,
            AnswerOption::B, AnswerOption::A, AnswerOption::D, AnswerOption::C, AnswerOption::B,
        ]);

    Storage::disk('public')->assertExists($topic->image_path);
});

test('human topic seeder is idempotent', function () {
    $this->seed(HumanTopicSeeder::class);
    $firstIds = QuizQuestion::query()->orderBy('question_order')->pluck('question_id')->all();
    $this->seed(HumanTopicSeeder::class);

    expect(Topic::query()->where('topic_order', 2)->count())->toBe(1)
        ->and(QuizQuestion::query()->count())->toBe(10)
        ->and(QuizQuestion::query()->orderBy('question_order')->pluck('question_id')->all())->toBe($firstIds);
});

test('students can open the human notes and start its ten-question quiz', function () {
    $this->seed(HumanTopicSeeder::class);
    $year = AcademicYear::factory()->active()->create();
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
    ]);
    $topic = Topic::query()->where('topic_order', 2)->sole();

    $this->actingAs($student)->get(route('student.learning.show', $topic))
        ->assertOk()
        ->assertSee('Manusia')
        ->assertSee('/storage/topics/images/sains-tahun-6-unit-2.jpg')
        ->assertSee('Mula Kuiz');

    $this->post(route('student.quiz.start', $topic))->assertRedirect();
    $attempt = $student->quizAttempts()->sole();

    $this->get(route('student.quiz.show', $attempt))
        ->assertOk()
        ->assertSee('Apakah maksud pembiakan manusia?')
        ->assertSee('Soalan 1 daripada 10');
});
