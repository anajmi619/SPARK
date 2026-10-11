<?php

use App\Enums\TopicStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createTopicImage(string $name): UploadedFile
{
    $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9JbF0AAAAASUVORK5CYII=', true);

    if ($content === false) {
        throw new RuntimeException('Unable to create the PNG test fixture.');
    }

    return UploadedFile::fake()->createWithContent($name, $content);
}

test('YouTube links accept only supported video URLs', function (string $url, bool $valid) {
    $teacher = User::factory()->create();
    $response = $this->actingAs($teacher)->post(route('teacher.topics.store'), [
        'topic_name' => 'Video Sains', 'youtube_url' => $url, 'status' => 'published',
    ]);
    if ($valid) {
        $response->assertSessionHasNoErrors();
        expect(Topic::query()->sole()->youtubeEmbedUrl())->toBe('https://www.youtube-nocookie.com/embed/abcdefghijk');
    } else {
        $response->assertSessionHasErrors('youtube_url');
        expect(Topic::query()->count())->toBe(0);
    }
})->with([
    ['https://www.youtube.com/watch?v=abcdefghijk&list=PL123', true],
    ['https://youtu.be/abcdefghijk?t=12', true],
    ['https://youtube.com/embed/abcdefghijk', true],
    ['https://m.youtube.com/shorts/abcdefghijk', true],
    ['https://youtube.com.evil.test/watch?v=abcdefghijk', false],
    ['https://youtube.com@evil.test/watch?v=abcdefghijk', false],
    ['https://youtube.com/watch?v[]=abcdefghijk', false],
    ['https://youtu.be/not-valid', false],
    ['javascript:alert(1)', false],
    ['https://youtube.com/playlist?list=PL123', false],
    ['https://youtube.com:8080/watch?v=abcdefghijk', false],
]);

test('a blank YouTube link removes the video without affecting PDF content', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1, 'pdf_path' => 'topics/documents/notes.pdf', 'youtube_url' => 'https://youtu.be/abcdefghijk']);
    $this->actingAs($teacher)->patch(route('teacher.topics.update', $topic), ['youtube_url' => '', 'status' => 'published'])->assertSessionHasNoErrors();
    expect($topic->refresh()->youtube_url)->toBeNull()->and($topic->pdf_path)->toBe('topics/documents/notes.pdf');
});

test('a non-string YouTube input returns validation errors rather than a server error', function () {
    $this->actingAs(User::factory()->create())->post(route('teacher.topics.store'), [
        'topic_name' => 'Daya', 'content_text' => 'Nota sains.', 'youtube_url' => ['invalid'], 'status' => 'draft',
    ])->assertSessionHasErrors('youtube_url');
});

function createTopicManagementStudent(): User
{
    $academicYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();

    return User::factory()->student()->active()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
    ]);
}

test('only teachers can access topic management', function () {
    $student = createTopicManagementStudent();

    $this->get(route('teacher.topics.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($student)
        ->get(route('teacher.topics.index'))
        ->assertForbidden();
});

test('teachers can view configured modules and the add-module control', function () {
    $teacher = User::factory()->create();
    $firstTopic = Topic::factory()->create([
        'topic_order' => 1,
        'topic_name' => 'Benda Hidup',
        'content_text' => 'Kandungan tentang benda hidup.',
    ]);
    $secondTopic = Topic::factory()->create([
        'topic_order' => 2,
        'topic_name' => 'Jirim',
        'content_text' => null,
    ]);

    $this->actingAs($teacher)
        ->get(route('teacher.topics.index'))
        ->assertOk()
        ->assertSeeInOrder([$firstTopic->topic_name, $secondTopic->topic_name])
        ->assertSee('Tambah Modul')
        ->assertSee('Tiada kandungan pembelajaran.');
});

test('teachers can open the create module form', function () {
    $teacher = User::factory()->create();

    $this->actingAs($teacher)
        ->get(route('teacher.topics.create'))
        ->assertOk()
        ->assertSee('Tambah Modul Pembelajaran')
        ->assertSee('Fail PDF')
        ->assertDontSee('name="image_file"', false)
        ->assertSee('maksimum 50 MB');
});

test('teachers can create a module with a PDF and a YouTube link', function () {
    Storage::fake('public');
    Storage::fake('local');

    $teacher = User::factory()->create();
    $pdf = UploadedFile::fake()->createWithContent('tenaga.pdf', "%PDF-1.4\n%%EOF")->size(3072);

    $this->actingAs($teacher)
        ->post(route('teacher.topics.store'), [
            'topic_name' => 'Tenaga',
            'content_text' => 'Tenaga membolehkan sesuatu objek melakukan kerja.',
            'pdf_file' => $pdf,
            'youtube_url' => 'https://youtu.be/abcdefghijk',
            'status' => TopicStatus::Published->value,
        ])
        ->assertRedirect(route('teacher.topics.index', absolute: false));

    $topic = Topic::query()->where('topic_name', 'Tenaga')->firstOrFail();

    expect($topic->topic_order)->toBe(1)
        ->and($topic->status)->toBe(TopicStatus::Published)
        ->and($topic->created_by)->toBe($teacher->user_id)
        ->and($topic->image_path)->toBeNull()
        ->and($topic->pdf_path)->toStartWith('topics/documents/')
        ->and($topic->youtube_url)->toBe('https://youtu.be/abcdefghijk');
    Storage::disk('local')->assertExists($topic->pdf_path);
    Storage::disk('public')->assertMissing($topic->pdf_path);
});

test('teachers can replace or remove uploaded media from a module', function () {
    Storage::fake('public');
    Storage::fake('local');

    Storage::disk('public')->put('topics/images/old-image.png', 'old image');
    Storage::disk('local')->put('topics/documents/old.pdf', '%PDF-1.4');

    $teacher = User::factory()->create();
    $topic = Topic::factory()->create([
        'topic_order' => 1,
        'image_path' => 'topics/images/old-image.png',
        'pdf_path' => 'topics/documents/old.pdf',
        'youtube_url' => 'https://youtu.be/abcdefghijk',
    ]);
    $replacementPdf = UploadedFile::fake()->createWithContent('replacement.pdf', "%PDF-1.4\n%%EOF");

    $this->actingAs($teacher)
        ->patch(route('teacher.topics.update', $topic), [
            'content_text' => 'Kandungan pembelajaran yang dikemas kini.',
            'pdf_file' => $replacementPdf,
            'youtube_url' => '',
            'status' => TopicStatus::Draft->value,
        ])
        ->assertRedirect(route('teacher.topics.index', absolute: false));

    $topic->refresh();

    expect($topic->pdf_path)->toStartWith('topics/documents/')
        ->and($topic->pdf_path)->not->toBe('topics/documents/old.pdf')
        ->and($topic->image_path)->toBe('topics/images/old-image.png')
        ->and($topic->youtube_url)->toBeNull();
    Storage::disk('public')->assertExists('topics/images/old-image.png');
    Storage::disk('local')->assertMissing('topics/documents/old.pdf');
    Storage::disk('local')->assertExists($topic->pdf_path);
});

test('teachers cannot publish a module without learning content', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create([
        'topic_order' => 1,
        'content_text' => null,
        'status' => TopicStatus::Draft,
    ]);

    $this->actingAs($teacher)
        ->from(route('teacher.topics.edit', $topic))
        ->patch(route('teacher.topics.update', $topic), [
            'content_text' => '   ',
            'status' => TopicStatus::Published->value,
        ])
        ->assertRedirect(route('teacher.topics.edit', $topic, absolute: false))
        ->assertSessionHasErrors('content_text');

    expect($topic->refresh()->status)->toBe(TopicStatus::Draft)
        ->and($topic->content_text)->toBeNull();
});

test('topic management rejects invalid PDF files and YouTube links', function () {
    $teacher = User::factory()->create();

    $this->actingAs($teacher)
        ->from(route('teacher.topics.create'))
        ->post(route('teacher.topics.store'), [
            'topic_name' => 'Tenaga',
            'content_text' => 'Kandungan pembelajaran yang sah.',
            'pdf_file' => UploadedFile::fake()->create('nota.txt', 10, 'text/plain'),
            'youtube_url' => 'https://example.com/watch?v=abcdefghijk',
            'status' => TopicStatus::Draft->value,
        ])
        ->assertRedirect(route('teacher.topics.create', absolute: false))
        ->assertSessionHasErrors(['pdf_file', 'youtube_url']);

    expect(Topic::query()->where('topic_name', 'Tenaga')->exists())->toBeFalse();
});

test('deleting a module removes its stored media and resequences later modules', function () {
    Storage::fake('public');
    Storage::fake('local');

    Storage::disk('local')->put('topics/documents/jirim.pdf', '%PDF-1.4');
    Storage::disk('public')->put('topics/images/jirim.png', 'image');

    $teacher = User::factory()->create();
    Topic::factory()->create(['topic_order' => 1, 'topic_name' => 'Benda Hidup']);
    $topicToDelete = Topic::factory()->create([
        'topic_order' => 2,
        'topic_name' => 'Jirim',
        'image_path' => 'topics/images/jirim.png',
        'pdf_path' => 'topics/documents/jirim.pdf',
        'youtube_url' => 'https://youtu.be/abcdefghijk',
    ]);
    $remainingTopic = Topic::factory()->create(['topic_order' => 3, 'topic_name' => 'Daya']);

    $this->actingAs($teacher)
        ->delete(route('teacher.topics.destroy', $topicToDelete))
        ->assertRedirect(route('teacher.topics.index', absolute: false));

    expect(Topic::query()->whereKey($topicToDelete)->exists())->toBeFalse()
        ->and($remainingTopic->refresh()->topic_order)->toBe(2);
    Storage::disk('public')->assertMissing('topics/images/jirim.png');
    Storage::disk('local')->assertMissing('topics/documents/jirim.pdf');
});

test('teachers cannot delete a module that already has quiz questions', function () {
    $teacher = User::factory()->create();
    $topic = Topic::factory()->create(['topic_order' => 1]);
    QuizQuestion::factory()->create(['topic_id' => $topic->topic_id]);

    $this->actingAs($teacher)
        ->from(route('teacher.topics.index'))
        ->delete(route('teacher.topics.destroy', $topic))
        ->assertRedirect(route('teacher.topics.index', absolute: false))
        ->assertSessionHasErrors('topic');

    expect($topic->refresh()->exists)->toBeTrue();
});

test('teachers cannot create more than twelve modules', function () {
    Storage::fake('local');
    $teacher = User::factory()->create();

    foreach (range(1, 12) as $topicOrder) {
        Topic::factory()->create(['topic_order' => $topicOrder]);
    }

    $this->actingAs($teacher)
        ->from(route('teacher.topics.create'))
        ->post(route('teacher.topics.store'), [
            'topic_name' => 'Modul Ketiga Belas',
            'pdf_file' => UploadedFile::fake()->createWithContent('nota.pdf', "%PDF-1.4\n%%EOF"),
            'content_text' => 'Kandungan pembelajaran tambahan.',
            'status' => TopicStatus::Draft->value,
        ])
        ->assertRedirect(route('teacher.topics.create', absolute: false))
        ->assertSessionHasErrors('topic_name');

    expect(Topic::query()->count())->toBe(12);
    expect(Storage::disk('local')->allFiles('topics/documents'))->toBeEmpty();
});
