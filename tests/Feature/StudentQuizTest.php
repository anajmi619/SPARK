<?php

use App\Enums\AiFeedbackStatus;
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
use App\Services\AiFeedbackService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function createQuizStudent(): User
{
    $academicYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();

    return User::factory()->student()->active()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
    ]);
}

function createPublishedQuizTopic(): Topic
{
    return Topic::factory()->create([
        'topic_order' => 1,
        'topic_name' => 'Daya',
        'content_text' => 'Daya boleh mengubah pergerakan sesuatu objek.',
        'status' => TopicStatus::Published,
    ]);
}

function createActiveQuizQuestion(Topic $topic, int $questionOrder, AnswerOption $correctOption = AnswerOption::A): QuizQuestion
{
    return QuizQuestion::factory()->create([
        'topic_id' => $topic->topic_id,
        'question_order' => $questionOrder,
        'question_text' => "Soalan kuiz {$questionOrder}",
        'option_a' => "Pilihan A {$questionOrder}",
        'option_b' => "Pilihan B {$questionOrder}",
        'option_c' => "Pilihan C {$questionOrder}",
        'option_d' => "Pilihan D {$questionOrder}",
        'correct_option' => $correctOption,
        'curriculum_context' => "Konteks bagi soalan {$questionOrder}.",
        'status' => QuestionStatus::Active,
    ]);
}

test('question images precede text and answers stay secret until server evaluation', function (string $selected) {
    Storage::fake('public');
    Http::fake();
    config(['quiz.ai_feedback_enabled' => false]);
    Storage::disk('public')->put('quiz/images/question.png', 'image');
    Storage::disk('public')->put('quiz/images/option.png', 'image');
    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1, AnswerOption::C);
    $question->update(['image_path' => 'quiz/images/question.png', 'option_c_image_path' => 'quiz/images/option.png', 'option_c' => '', 'explanation' => 'Penerangan sulit sehingga jawapan dihantar.']);
    createActiveQuizQuestion($topic, 2);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->sole();
    $this->get(route('student.quiz.show', $attempt))->assertOk()
        ->assertSeeInOrder(['quiz/images/question.png', $question->question_text, 'quiz/images/option.png'])
        ->assertDontSee('correct_option')->assertDontSee('Jawapan Betul')->assertDontSee($question->explanation);
    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => $selected])->assertRedirect();
    $answer = QuizAnswer::query()->sole();
    $question->update(['explanation' => 'Penerangan baharu.', 'option_c' => 'Teks baharu']);
    $response = $this->get(route('student.quiz.feedback', ['attempt' => $attempt, 'answer' => $answer]))->assertOk()
        ->assertSee('Jawapan Betul')->assertSee('quiz/images/option.png')
        ->assertSee('Penerangan sulit sehingga jawapan dihantar.')->assertDontSee('Penerangan baharu.')
        ->assertDontSee('name="selected_option"', false)->assertSee('Seterusnya');
    if ($selected === 'C') {
        $response->assertSee('Hebat, jawapan anda betul!')->assertDontSee('Maklum Balas AI');
    } else {
        $response->assertSee('Maklum Balas AI')->assertSee('Maklum balas AI tidak dapat dijana buat masa ini. Sila rujuk jawapan betul dan baca semula kandungan modul.');
    }
    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'C'])->assertNotFound();
    expect(QuizAnswer::query()->count())->toBe(1)->and($answer->refresh()->selected_option->value)->toBe($selected);
    Http::assertNothingSent();
})->with(['C', 'A']);

test('enabled AI without a Gemini key returns the fallback without network traffic', function () {
    Http::fake();
    config(['quiz.ai_feedback_enabled' => true, 'services.gemini.api_key' => null]);
    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->sole();
    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'B'])->assertRedirect();
    expect(QuizAnswer::query()->sole()->ai_feedback_status)->toBe(AiFeedbackStatus::Failed);
    Http::assertNothingSent();
});

test('missing quiz images use an accessible empty state', function () {
    Storage::fake('public');
    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1);
    $question->update(['image_path' => 'quiz/images/missing.png']);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $this->get(route('student.quiz.show', QuizAttempt::query()->sole()))->assertOk()->assertSee('Imej tidak tersedia.')->assertDontSee('src="http://localhost:8000/storage/quiz/images/missing.png"', false);
});

test('only students can start a published topic quiz', function () {
    $topic = createPublishedQuizTopic();
    createActiveQuizQuestion($topic, 1);
    $teacher = User::factory()->create();

    $this->post(route('student.quiz.start', $topic))
        ->assertRedirect(route('login'));

    $this->actingAs($teacher)
        ->post(route('student.quiz.start', $topic))
        ->assertForbidden();
});

test('starting a quiz creates one in-progress attempt and resumes it when started again', function () {
    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    createActiveQuizQuestion($topic, 1);

    $this->actingAs($student)
        ->post(route('student.quiz.start', $topic))
        ->assertRedirect();

    $attempt = QuizAttempt::query()->firstOrFail();

    expect($attempt->student_id)->toBe($student->user_id)
        ->and($attempt->topic_id)->toBe($topic->topic_id)
        ->and($attempt->attempt_number)->toBe(1)
        ->and($attempt->status)->toBe(AttemptStatus::InProgress);

    $this->actingAs($student)
        ->post(route('student.quiz.start', $topic))
        ->assertRedirect(route('student.quiz.show', $attempt, absolute: false));

    expect(QuizAttempt::query()->count())->toBe(1);
});

test('students see only active quiz questions', function () {
    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $activeQuestion = createActiveQuizQuestion($topic, 1);
    $inactiveQuestion = QuizQuestion::factory()->create([
        'topic_id' => $topic->topic_id,
        'question_order' => 2,
        'question_text' => 'Soalan tidak aktif',
        'status' => QuestionStatus::Inactive,
    ]);

    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->get(route('student.quiz.show', $attempt))
        ->assertOk()
        ->assertSee($activeQuestion->question_text)
        ->assertSee('Soalan 1 daripada 1')
        ->assertDontSee($inactiveQuestion->question_text);
});

test('a correct answer is recorded without an AI request and completes a one-question quiz', function () {
    Http::fake();
    config(['quiz.ai_feedback_enabled' => true, 'services.gemini.api_key' => 'test-gemini-key']);

    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1, AnswerOption::C);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), [
            'selected_option' => AnswerOption::C->value,
        ])
        ->assertRedirect();

    $answer = QuizAnswer::query()->firstOrFail();

    expect($answer->is_correct)->toBeTrue()
        ->and($answer->question_snapshot)->toBe($question->question_text)
        ->and($answer->selected_answer_snapshot)->toBe($question->option_c)
        ->and($answer->correct_answer_snapshot)->toBe($question->option_c)
        ->and($answer->ai_feedback_status)->toBe(AiFeedbackStatus::NotRequired)
        ->and($answer->ai_feedback_text)->toBeNull()
        ->and($attempt->refresh()->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->score)->toBe(1);
    Http::assertNothingSent();
});

test('an incorrect answer can use the enabled feedback integration without HTTP', function () {
    config(['quiz.ai_feedback_enabled' => true]);
    Http::fake();
    $feedback = $this->mock(AiFeedbackService::class);
    $feedback->shouldReceive('canGenerate')->once()->andReturnTrue();
    $feedback->shouldReceive('generate')->once()
        ->andReturn('Daya tolakan berlaku apabila sesuatu objek ditolak menjauhi penolak.');

    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1, AnswerOption::A);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), [
            'selected_option' => AnswerOption::B->value,
        ])
        ->assertRedirect();

    $answer = QuizAnswer::query()->firstOrFail();

    expect($answer->is_correct)->toBeFalse()
        ->and($answer->ai_feedback_status)->toBe(AiFeedbackStatus::Generated)
        ->and($answer->ai_feedback_text)->toBe('Daya tolakan berlaku apabila sesuatu objek ditolak menjauhi penolak.');
    Http::assertNothingSent();

    $this->actingAs($student)
        ->get(route('student.quiz.feedback', ['attempt' => $attempt, 'answer' => $answer]))
        ->assertOk()
        ->assertSee('Jawapan Anda')
        ->assertSee('Maklum Balas AI')
        ->assertSee($answer->ai_feedback_text)
        ->assertSee('Lihat Keputusan');
});

test('Gemini feedback is saved and rendered without a second request on refresh', function () {
    config(['quiz.ai_feedback_enabled' => true, 'services.gemini.api_key' => 'test-gemini-key']);
    Http::preventStrayRequests();
    $feedback = 'Pilihan B kurang tepat. Pilihan A menerangkan konsep dengan betul. Teruskan usaha!';
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $feedback]]]]],
    ])]);
    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->sole();
    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'B'])->assertRedirect();
    $answer = QuizAnswer::query()->sole();

    expect($answer->ai_feedback_status)->toBe(AiFeedbackStatus::Generated)
        ->and($answer->ai_feedback_text)->toBe($feedback)
        ->and($attempt->refresh()->score)->toBe(0)
        ->and($attempt->status)->toBe(AttemptStatus::Completed);
    for ($refresh = 0; $refresh < 2; $refresh++) {
        $this->get(route('student.quiz.feedback', ['attempt' => $attempt, 'answer' => $answer]))
            ->assertOk()->assertSee($feedback)->assertDontSee('test-gemini-key');
    }
    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'B'])->assertNotFound();
    Http::assertSentCount(1);
});

test('Gemini quota exhaustion does not prevent quiz completion', function () {
    config(['quiz.ai_feedback_enabled' => true, 'services.gemini.api_key' => 'test-gemini-key']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);
    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->sole();
    $this->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), ['selected_option' => 'B'])->assertRedirect();
    $answer = QuizAnswer::query()->sole();

    expect($answer->ai_feedback_status)->toBe(AiFeedbackStatus::Failed)
        ->and($attempt->refresh()->status)->toBe(AttemptStatus::Completed);
    $this->get(route('student.quiz.feedback', ['attempt' => $attempt, 'answer' => $answer]))
        ->assertOk()->assertSee('Maklum balas AI tidak dapat dijana buat masa ini.');
    Http::assertSentCount(1);
});

test('an incorrect answer remains available when AI feedback is not configured', function () {
    config(['quiz.ai_feedback_enabled' => false]);
    Http::fake();

    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $question = createActiveQuizQuestion($topic, 1, AnswerOption::A);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]), [
            'selected_option' => AnswerOption::D->value,
        ]);

    $answer = QuizAnswer::query()->firstOrFail();

    expect($answer->ai_feedback_status)->toBe(AiFeedbackStatus::Failed)
        ->and($answer->ai_feedback_text)->toBeNull();
    Http::assertNothingSent();

    $this->actingAs($student)
        ->get(route('student.quiz.feedback', ['attempt' => $attempt, 'answer' => $answer]))
        ->assertOk()
        ->assertSee('Maklum balas AI tidak dapat dijana buat masa ini.');
});

test('students continue through questions and receive the completed score', function () {
    config(['quiz.ai_feedback_enabled' => false]);

    $student = createQuizStudent();
    $topic = createPublishedQuizTopic();
    $firstQuestion = createActiveQuizQuestion($topic, 1, AnswerOption::A);
    $secondQuestion = createActiveQuizQuestion($topic, 2, AnswerOption::B);
    $this->actingAs($student)->post(route('student.quiz.start', $topic));
    $attempt = QuizAttempt::query()->firstOrFail();

    $this->actingAs($student)->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $firstQuestion]), [
        'selected_option' => AnswerOption::A->value,
    ]);

    $this->actingAs($student)
        ->get(route('student.quiz.show', $attempt))
        ->assertOk()
        ->assertSee($secondQuestion->question_text)
        ->assertSee('Soalan 2 daripada 2');

    $this->actingAs($student)->post(route('student.quiz.answer', ['attempt' => $attempt, 'question' => $secondQuestion]), [
        'selected_option' => AnswerOption::D->value,
    ]);

    $this->actingAs($student)
        ->get(route('student.quiz.result', $attempt))
        ->assertOk()
        ->assertSee('1')
        ->assertSee('daripada 2');

    expect($attempt->refresh()->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->score)->toBe(1)
        ->and($attempt->answers()->count())->toBe(2);
});

test('students cannot access another students quiz attempt', function () {
    $firstStudent = createQuizStudent();
    $secondStudent = User::factory()->student()->active()->create([
        'academic_year_id' => $firstStudent->academic_year_id,
        'current_level_id' => $firstStudent->current_level_id,
    ]);
    $topic = createPublishedQuizTopic();
    createActiveQuizQuestion($topic, 1);
    $attempt = QuizAttempt::factory()->create([
        'student_id' => $firstStudent->user_id,
        'topic_id' => $topic->topic_id,
    ]);

    $this->actingAs($secondStudent)
        ->get(route('student.quiz.show', $attempt))
        ->assertNotFound();
});
