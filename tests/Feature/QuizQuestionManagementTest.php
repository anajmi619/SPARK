<?php

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createQuizQuestionManagementStudent(): User
{
    $academicYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();

    return User::factory()->student()->active()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
    ]);
}

function quizQuestionData(array $overrides = []): array
{
    return array_merge([
        'question_text' => 'Apakah yang berlaku apabila objek ditolak?',
        'option_a' => 'Objek bergerak menjauhi penolak',
        'option_b' => 'Objek hilang',
        'option_c' => 'Objek bertukar warna',
        'option_d' => 'Objek menjadi lebih kecil',
        'correct_option' => AnswerOption::A->value,
        'curriculum_context' => 'Daya tolakan menggerakkan objek menjauhi penolak.',
        'status' => QuestionStatus::Active->value,
    ], $overrides);
}

function quizImageFixture(string $name = 'question.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9JbF0AAAAASUVORK5CYII='));
}

test('teachers can upload replace remove and delete question and option images', function () {
    Storage::fake('public');
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    $data = quizQuestionData(['explanation' => 'Tolakan menjauhkan objek.']);
    foreach (QuizQuestion::imageFields() as $field) {
        $data[$field] = quizImageFixture();
    }
    $data['option_a'] = '';
    $this->actingAs($teacher)->post(route('teacher.questions.store', $topic), $data)->assertSessionHasNoErrors();
    $question = QuizQuestion::query()->sole();
    expect($question->option_a)->toBe('')->and($question->explanation)->toBe('Tolakan menjauhkan objek.');
    $oldPaths = [];
    foreach (QuizQuestion::imageFields() as $field) {
        $oldPaths[$field] = $question->getAttribute($field.'_path');
        Storage::disk('public')->assertExists($oldPaths[$field]);
    }
    $url = route('teacher.questions.update', ['topic' => $topic, 'question' => $question]);
    $this->actingAs($teacher)->get(route('teacher.questions.edit', ['topic' => $topic, 'question' => $question]))
        ->assertOk()->assertSee('multipart/form-data')->assertSee($question->image_path);
    // Keeping an existing image is enough for an image-only option.
    $this->patch($url, quizQuestionData(['option_a' => '']))->assertSessionHasNoErrors();
    $this->patch($url, quizQuestionData(['option_a' => '', 'remove_option_a_image' => 1]))->assertSessionHasErrors('option_a');
    Storage::disk('public')->assertExists($oldPaths['option_a_image']);
    $this->patch($url, quizQuestionData(['image' => quizImageFixture('new.png'), 'remove_option_a_image' => 1]))->assertSessionHasNoErrors();
    $question->refresh();
    Storage::disk('public')->assertMissing($oldPaths['image']);
    Storage::disk('public')->assertMissing($oldPaths['option_a_image']);
    Storage::disk('public')->assertExists($question->image_path);
    expect($question->option_a_image_path)->toBeNull();
    $this->delete(route('teacher.questions.destroy', ['topic' => $topic, 'question' => $question]))->assertSessionHasNoErrors();
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('question images reject oversized or unsafe files and explanations are limited', function () {
    Storage::fake('public');
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    $this->actingAs($teacher)->post(route('teacher.questions.store', $topic), quizQuestionData([
        'image' => quizImageFixture()->size(5121),
        'option_a_image' => UploadedFile::fake()->createWithContent('script.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
        'option_b_image' => UploadedFile::fake()->createWithContent('fake.png', '<?php echo 1;'),
        'explanation' => str_repeat('a', 501),
    ]))->assertSessionHasErrors(['image', 'option_a_image', 'option_b_image', 'explanation']);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('question and answer option images may be up to five megabytes', function () {
    Storage::fake('public');
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);

    $this->actingAs($teacher)->post(route('teacher.questions.store', $topic), quizQuestionData([
        'image' => quizImageFixture('question.png')->size(5120),
        'option_a_image' => quizImageFixture('option-a.png')->size(5120),
    ]))->assertSessionHasNoErrors();

    $question = QuizQuestion::query()->sole();
    Storage::disk('public')->assertExists($question->image_path);
    Storage::disk('public')->assertExists($question->option_a_image_path);
});

test('a non-string explanation is rejected without a server error', function () {
    $topic = Topic::factory()->create(['topic_order' => 1]);
    $this->actingAs(User::factory()->create())->post(route('teacher.questions.store', $topic), quizQuestionData(['explanation' => ['invalid']]))->assertSessionHasErrors('explanation');
});

test('failed question creation cleans up newly uploaded images', function () {
    Storage::fake('public');
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    foreach (range(1, 10) as $order) {
        QuizQuestion::factory()->create(['topic_id' => $topic->topic_id, 'question_order' => $order]);
    }
    $this->actingAs($teacher)->post(route('teacher.questions.store', $topic), quizQuestionData(['image' => quizImageFixture()]))->assertSessionHasErrors('question_text');
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('only teachers can access quiz question management', function () {
    $topic = Topic::factory()->create(['topic_order' => 1]);
    $student = createQuizQuestionManagementStudent();

    $this->get(route('teacher.questions.index', $topic))
        ->assertRedirect(route('login'));

    $this->actingAs($student)
        ->get(route('teacher.questions.index', $topic))
        ->assertForbidden();
});

test('teachers can view a topic question list and the create form', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1, 'topic_name' => 'Daya']);
    $question = QuizQuestion::factory()->create([
        'topic_id' => $topic->topic_id,
        'question_order' => 1,
        'question_text' => 'Apakah contoh daya tolakan?',
        'correct_option' => AnswerOption::C,
        'option_c' => 'Menekan butang',
    ]);

    $this->actingAs($teacher)
        ->get(route('teacher.questions.index', $topic))
        ->assertOk()
        ->assertSee('Urus Soalan Kuiz')
        ->assertSee($question->question_text)
        ->assertSee('C.')
        ->assertSee('Menekan butang')
        ->assertSee('Tambah Soalan');

    $this->actingAs($teacher)
        ->get(route('teacher.questions.create', $topic))
        ->assertOk()
        ->assertSee('Pilihan A')
        ->assertSee('Jawapan Betul');
});

test('teachers can add a four-option question to a topic', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    QuizQuestion::factory()->create(['topic_id' => $topic->topic_id, 'question_order' => 1]);

    $this->actingAs($teacher)
        ->post(route('teacher.questions.store', $topic), quizQuestionData())
        ->assertRedirect(route('teacher.questions.index', $topic, absolute: false));

    $question = QuizQuestion::query()
        ->where('topic_id', $topic->topic_id)
        ->where('question_order', 2)
        ->firstOrFail();

    expect($question->question_text)->toBe('Apakah yang berlaku apabila objek ditolak?')
        ->and($question->correct_option)->toBe(AnswerOption::A)
        ->and($question->status)->toBe(QuestionStatus::Active)
        ->and($question->curriculum_context)->toBe('Daya tolakan menggerakkan objek menjauhi penolak.');
});

test('quiz question validation requires four options and one valid correct option', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);

    $this->actingAs($teacher)
        ->from(route('teacher.questions.create', $topic))
        ->post(route('teacher.questions.store', $topic), quizQuestionData([
            'option_d' => '',
            'correct_option' => 'E',
        ]))
        ->assertRedirect(route('teacher.questions.create', $topic, absolute: false))
        ->assertSessionHasErrors(['option_d', 'correct_option']);

    expect(QuizQuestion::query()->where('topic_id', $topic->topic_id)->exists())->toBeFalse();
});

test('teachers cannot create more than ten questions for one topic', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);

    foreach (range(1, 10) as $questionOrder) {
        QuizQuestion::factory()->create([
            'topic_id' => $topic->topic_id,
            'question_order' => $questionOrder,
        ]);
    }

    $this->actingAs($teacher)
        ->from(route('teacher.questions.create', $topic))
        ->post(route('teacher.questions.store', $topic), quizQuestionData())
        ->assertRedirect(route('teacher.questions.create', $topic, absolute: false))
        ->assertSessionHasErrors('question_text');

    expect(QuizQuestion::query()->where('topic_id', $topic->topic_id)->count())->toBe(10);
});

test('teachers can update a question and make it inactive', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    $question = QuizQuestion::factory()->create([
        'topic_id' => $topic->topic_id,
        'question_order' => 1,
    ]);

    $this->actingAs($teacher)
        ->patch(route('teacher.questions.update', ['topic' => $topic, 'question' => $question]), quizQuestionData([
            'question_text' => 'Apakah contoh daya tarikan?',
            'correct_option' => AnswerOption::B->value,
            'status' => QuestionStatus::Inactive->value,
            'curriculum_context' => '',
        ]))
        ->assertRedirect(route('teacher.questions.index', $topic, absolute: false));

    expect($question->refresh()->question_text)->toBe('Apakah contoh daya tarikan?')
        ->and($question->correct_option)->toBe(AnswerOption::B)
        ->and($question->status)->toBe(QuestionStatus::Inactive)
        ->and($question->curriculum_context)->toBeNull();
});

test('questions cannot be managed through another topic URL', function () {
    $teacher = User::factory()->create();
    $firstTopic = Topic::factory()->create(['topic_order' => 1]);
    $secondTopic = Topic::factory()->create(['topic_order' => 2]);
    $question = QuizQuestion::factory()->create([
        'topic_id' => $secondTopic->topic_id,
        'question_order' => 1,
    ]);

    $this->actingAs($teacher)
        ->get(route('teacher.questions.edit', ['topic' => $firstTopic, 'question' => $question]))
        ->assertNotFound();
});

test('deleting an unused question resequences later questions', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    QuizQuestion::factory()->create(['topic_id' => $topic->topic_id, 'question_order' => 1]);
    $questionToDelete = QuizQuestion::factory()->create(['topic_id' => $topic->topic_id, 'question_order' => 2]);
    $remainingQuestion = QuizQuestion::factory()->create(['topic_id' => $topic->topic_id, 'question_order' => 3]);

    $this->actingAs($teacher)
        ->delete(route('teacher.questions.destroy', ['topic' => $topic, 'question' => $questionToDelete]))
        ->assertRedirect(route('teacher.questions.index', $topic, absolute: false));

    expect(QuizQuestion::query()->whereKey($questionToDelete)->exists())->toBeFalse()
        ->and($remainingQuestion->refresh()->question_order)->toBe(2);
});

test('teachers cannot delete a question that has student answers', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    $question = QuizQuestion::factory()->create([
        'topic_id' => $topic->topic_id,
        'question_order' => 1,
    ]);
    $attempt = QuizAttempt::factory()->completed()->create(['topic_id' => $topic->topic_id]);
    QuizAnswer::factory()->create([
        'attempt_id' => $attempt->attempt_id,
        'question_id' => $question->question_id,
    ]);

    $this->actingAs($teacher)
        ->from(route('teacher.questions.index', $topic))
        ->delete(route('teacher.questions.destroy', ['topic' => $topic, 'question' => $question]))
        ->assertRedirect(route('teacher.questions.index', $topic, absolute: false))
        ->assertSessionHasErrors('question');

    expect($question->refresh()->exists)->toBeTrue();
});
