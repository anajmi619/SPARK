<?php

use App\Actions\Student\GenerateAiFeedback;
use App\Enums\AiFeedbackSource;
use App\Enums\AnswerOption;
use App\Models\QuizQuestion;
use App\Services\AiFeedbackService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'quiz.ai_feedback_enabled' => true,
        'services.gemini.api_key' => 'test-gemini-key',
        'services.gemini.model' => 'gemini-3.5-flash-lite',
    ]);
    $this->question = new QuizQuestion([
        'question_text' => 'Alat manakah digunakan untuk melihat mikroorganisma?',
        'option_a' => 'Mikroskop', 'option_b' => 'Pembaris',
        'option_c' => 'Jam randik', 'option_d' => 'Termometer',
        'correct_option' => AnswerOption::A,
        'explanation' => 'Mikroskop membesarkan imej hidupan seni.',
        'curriculum_context' => 'Mikroorganisma biasanya tidak dapat dilihat dengan mata kasar.',
    ]);
});

test('Gemini receives only quiz context and returns final text', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [
            ['thought' => true, 'text' => 'Internal reasoning should not appear.'],
            ['text' => 'Pembaris digunakan untuk mengukur panjang.'],
            ['text' => 'Mikroskop membesarkan imej mikroorganisma. Teruskan usaha!'],
        ]]]],
    ])]);

    $result = app(AiFeedbackService::class)->generate($this->question, AnswerOption::B);

    expect($result)->toBe("Pembaris digunakan untuk mengukur panjang.\nMikroskop membesarkan imej mikroorganisma. Teruskan usaha!");
    Http::assertSent(function (Request $request): bool {
        $data = json_decode($request['contents'][0]['parts'][0]['text'], true);

        return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent'
            && $request->method() === 'POST'
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && $data['jawapan_dipilih'] === 'B'
            && $data['jawapan_betul'] === 'A'
            && $data['penerangan_guru'] === $this->question->explanation
            && array_keys($data) === ['soalan', 'pilihan', 'jawapan_dipilih', 'jawapan_betul', 'penerangan_guru', 'konteks_pembelajaran', 'mengandungi_imej_yang_tidak_dihantar']
            && str_contains($request['systemInstruction']['parts'][0]['text'], 'Bahasa Melayu');
    });
    Http::assertSentCount(1);
});

test('Gemini skips disabled feedback missing keys and correct answers', function (bool $enabled, ?string $key, AnswerOption $selected) {
    Http::fake();
    config(['quiz.ai_feedback_enabled' => $enabled, 'services.gemini.api_key' => $key]);

    expect(app(AiFeedbackService::class)->generate($this->question, $selected))->toBeNull();
    Http::assertNothingSent();
})->with([
    'disabled' => [false, 'test-key', AnswerOption::B],
    'missing key' => [true, null, AnswerOption::B],
    'empty key' => [true, ' ', AnswerOption::B],
    'correct answer' => [true, 'test-key', AnswerOption::A],
]);

test('Gemini failures use fallback without logging keys or provider content', function (int $status) {
    Log::spy();
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'sensitive provider response']], $status)]);

    expect(app(AiFeedbackService::class)->generate($this->question, AnswerOption::B))->toBeNull();
    Log::shouldHaveReceived('warning')->once()->with('Gemini feedback request failed.', ['status' => $status]);
    Http::assertSentCount(1);
})->with([400, 403, 404, 429, 500, 503]);

test('Gemini connection failure returns fallback', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::failedConnection()]);

    expect(app(AiFeedbackService::class)->generate($this->question, AnswerOption::B))->toBeNull();
});

test('Gemini rejects blocked truncated empty and malformed output', function (array $payload) {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response($payload)]);

    expect(app(AiFeedbackService::class)->generate($this->question, AnswerOption::B))->toBeNull();
})->with([
    'blocked prompt' => [['promptFeedback' => ['blockReason' => 'SAFETY']]],
    'blocked candidate' => [['candidates' => [['finishReason' => 'SAFETY', 'content' => ['parts' => [['text' => 'blocked text']]]]]]],
    'truncated' => [['candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => 'incomplete text']]]]]]],
    'empty' => [[]],
    'wrong parts type' => [['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => 'invalid']]]]],
    'non-text' => [['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => ['invalid']]]]]]]],
    'whitespace' => [['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '  ']]]]]]],
]);

test('image questions without teacher explanations do not request speculative feedback', function () {
    Http::fake();
    $this->question->image_path = 'quiz/images/question.png';
    $this->question->explanation = null;

    expect(app(AiFeedbackService::class)->generate($this->question, AnswerOption::B))->toBeNull();
    Http::assertNothingSent();
});

test('invalid model configuration never sends a request', function () {
    Http::fake();
    config(['services.gemini.model' => '../models/invalid?key=bad']);

    expect(app(AiFeedbackService::class)->generate($this->question, AnswerOption::B))->toBeNull();
    Http::assertNothingSent();
});

test('generated feedback is cached for the same question version and selected option', function () {
    $question = QuizQuestion::factory()->create([
        'correct_option' => AnswerOption::A,
        'explanation' => 'Mikroskop digunakan untuk melihat objek yang sangat kecil.',
    ]);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'Gunakan mikroskop untuk melihat mikroorganisma.']]]]],
    ])]);

    $first = app(GenerateAiFeedback::class)->handle($question, AnswerOption::B, 10);
    $second = app(GenerateAiFeedback::class)->handle($question, AnswerOption::B, 11);

    expect($first['source'])->toBe(AiFeedbackSource::Api)
        ->and($second['source'])->toBe(AiFeedbackSource::Cache)
        ->and($second['text'])->toBe($first['text']);
    Http::assertSentCount(1);
});

test('per-student feedback limits stop additional uncached API requests', function () {
    config(['quiz.ai_feedback_per_student_hourly_limit' => 1, 'quiz.ai_feedback_daily_limit' => 100]);
    $question = QuizQuestion::factory()->create([
        'correct_option' => AnswerOption::A,
        'explanation' => 'Penerangan guru.',
    ]);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'Maklum balas pertama.']]]]],
    ])]);

    $first = app(GenerateAiFeedback::class)->handle($question, AnswerOption::B, 25);
    $second = app(GenerateAiFeedback::class)->handle($question, AnswerOption::C, 25);

    expect($first['source'])->toBe(AiFeedbackSource::Api)
        ->and($second['source'])->toBeNull()
        ->and($second['text'])->toBeNull();
    Http::assertSentCount(1);
    RateLimiter::clear('quiz-ai-feedback:student:25');
});
