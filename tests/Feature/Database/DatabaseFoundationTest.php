<?php

use App\Enums\AcademicYearStatus;
use App\Enums\AiFeedbackStatus;
use App\Enums\AnswerOption;
use App\Enums\AttemptStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('database contains the SPARK domain tables', function () {
    expect(Schema::hasTable('kelas'))->toBeTrue()
        ->and(Schema::hasTable('academic_years'))->toBeTrue()
        ->and(Schema::hasTable('levels'))->toBeTrue()
        ->and(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('topics'))->toBeTrue()
        ->and(Schema::hasTable('quiz_questions'))->toBeTrue()
        ->and(Schema::hasTable('quiz_attempts'))->toBeTrue()
        ->and(Schema::hasTable('quiz_answers'))->toBeTrue();
});

test('database seeder creates the planned years levels and confirmed topics', function () {
    $this->seed(DatabaseSeeder::class);

    expect(AcademicYear::query()->count())->toBe(2)
        ->and(AcademicYear::query()->where('status', AcademicYearStatus::Active)->value('year'))->toBe(2026)
        ->and(Level::query()->orderBy('level_number')->pluck('level_name')->all())->toBe([
            'Permulaan',
            'Peneroka Sains',
            'Penyiasat Sains',
            'Pakar Kuiz',
            'Pelajar Konsisten',
            'Pakar Sains',
            'Juara Sains',
        ])
        ->and(Topic::query()->orderBy('topic_order')->pluck('topic_name')->all())->toBe([
            'Kemahiran Saintifik',
            'Manusia',
            'Mikroorganisma',
            'Interaksi Antara Hidupan',
        ]);

    expect(Topic::query()->where('topic_order', 3)->value('content_text'))
        ->toContain('Mikroorganisma ialah hidupan seni')
        ->and(Topic::query()->where('topic_order', 4)->value('content_text'))
        ->toContain('Interaksi antara hidupan ialah hubungan');
});

test('student and teacher accounts retain their required relationships', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->create();

    expect($student->role)->toBe(UserRole::Student)
        ->and($student->status)->toBe(UserStatus::Pending)
        ->and($student->academicYear)->toBeInstanceOf(AcademicYear::class)
        ->and($student->currentLevel)->toBeInstanceOf(Level::class)
        ->and($teacher->role)->toBe(UserRole::Admin)
        ->and($teacher->academic_year_id)->toBeNull()
        ->and($teacher->current_level_id)->toBeNull()
        ->and($teacher->kelas_id)->toBeNull();
});

test('quiz records preserve relationships snapshots and enum states', function () {
    $student = User::factory()->student()->active()->create();
    $topic = Topic::factory()->create();
    $question = QuizQuestion::factory()->create(['topic_id' => $topic->topic_id]);
    $attempt = QuizAttempt::factory()->completed(8)->create([
        'student_id' => $student->user_id,
        'topic_id' => $topic->topic_id,
    ]);
    $answer = QuizAnswer::factory()->incorrectWithFeedback()->create([
        'attempt_id' => $attempt->attempt_id,
        'question_id' => $question->question_id,
        'selected_option' => AnswerOption::B,
    ]);

    expect($attempt->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->student->is($student))->toBeTrue()
        ->and($attempt->topic->is($topic))->toBeTrue()
        ->and($answer->selected_option)->toBe(AnswerOption::B)
        ->and($answer->ai_feedback_status)->toBe(AiFeedbackStatus::Generated)
        ->and($answer->question_snapshot)->not->toBeEmpty()
        ->and($attempt->answers->first()->is($answer))->toBeTrue();
});

test('database rejects duplicate usernames attempt numbers and answers', function () {
    $student = User::factory()->student()->active()->create(['username' => 'pelajar_satu']);
    $topic = Topic::factory()->create();
    $question = QuizQuestion::factory()->create(['topic_id' => $topic->topic_id]);
    $attempt = QuizAttempt::factory()->create([
        'student_id' => $student->user_id,
        'topic_id' => $topic->topic_id,
        'attempt_number' => 1,
    ]);
    QuizAnswer::factory()->create([
        'attempt_id' => $attempt->attempt_id,
        'question_id' => $question->question_id,
    ]);

    expect(fn () => User::factory()->create(['username' => 'pelajar_satu']))
        ->toThrow(QueryException::class)
        ->and(fn () => QuizAttempt::factory()->create([
            'student_id' => $student->user_id,
            'topic_id' => $topic->topic_id,
            'attempt_number' => 1,
        ]))->toThrow(QueryException::class)
        ->and(fn () => QuizAnswer::factory()->create([
            'attempt_id' => $attempt->attempt_id,
            'question_id' => $question->question_id,
        ]))->toThrow(QueryException::class);
});

test('historical learning records prevent student deletion', function () {
    $attempt = QuizAttempt::factory()->completed()->create();

    expect(fn () => $attempt->student->delete())->toThrow(QueryException::class);
});
