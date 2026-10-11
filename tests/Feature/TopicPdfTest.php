<?php

use App\Enums\AcademicYearStatus;
use App\Enums\TopicStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

function topicPdfUpload(): File
{
    return UploadedFile::fake()->createWithContent('nota.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
}

function topicWithPdf(TopicStatus $status = TopicStatus::Published): Topic
{
    $pdf = topicPdfUpload();
    Storage::disk('local')->put('topics/documents/nota.pdf', $pdf->getContent());

    return Topic::factory()->create(['status' => $status, 'pdf_path' => 'topics/documents/nota.pdf']);
}

test('a teacher can publish a PDF-only module and retain the PDF when editing text', function () {
    $teacher = User::factory()->create();
    $this->actingAs($teacher)->post(route('teacher.topics.store'), [
        'topic_name' => 'Daya', 'pdf_file' => topicPdfUpload(), 'status' => 'published',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $topic = Topic::query()->firstOrFail();
    $path = $topic->pdf_path;
    expect($topic->content_text)->toBeNull();

    $this->patch(route('teacher.topics.update', $topic), [
        'content_text' => '', 'status' => 'published',
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($topic->refresh()->pdf_path)->toBe($path);
    Storage::disk('local')->assertExists($path);
});

test('the last learning material cannot be removed from a published module', function () {
    $topic = topicWithPdf();
    $topic->update(['content_text' => null]);
    $this->actingAs(User::factory()->create())->patch(route('teacher.topics.update', $topic), [
        'remove_pdf' => 1, 'status' => 'published',
    ])->assertSessionHasErrors('content_text');
    expect($topic->refresh()->pdf_path)->not->toBeNull();
    Storage::disk('local')->assertExists($topic->pdf_path);

    $this->patch(route('teacher.topics.update', $topic), [
        'remove_pdf' => 1, 'status' => 'draft',
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($topic->refresh()->pdf_path)->toBeNull();
    Storage::disk('local')->assertMissing('topics/documents/nota.pdf');
});

test('PDF validation rejects non-PDF content and oversize files', function (string $kind) {
    $file = match ($kind) {
        'image' => UploadedFile::fake()->create('image.png', 10, 'image/png'),
        'disguised' => UploadedFile::fake()->createWithContent('nota.pdf', '<html>not a PDF</html>'),
        'oversize' => topicPdfUpload()->size(51201),
        'extension' => UploadedFile::fake()->createWithContent('nota.txt', "%PDF-1.4\n%%EOF"),
    };
    $fixture = $file;
    if ($kind === 'disguised' || $kind === 'extension') {
        $file = new UploadedFile($fixture->path(), $fixture->getClientOriginalName(), 'application/pdf', null, true);
    }
    $this->actingAs(User::factory()->create())->post(route('teacher.topics.store'), [
        'topic_name' => 'Daya', 'pdf_file' => $file, 'status' => 'published',
    ])->assertSessionHasErrors('pdf_file');
    expect(Topic::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['image', 'disguised', 'oversize', 'extension']);

test('image uploads are no longer accepted', function () {
    $this->actingAs(User::factory()->create())->post(route('teacher.topics.store'), [
        'topic_name' => 'Daya', 'image_file' => UploadedFile::fake()->create('image.png', 10, 'image/png'), 'status' => 'draft',
    ])->assertSessionHasErrors('image_file');
});

test('teacher and student pages use the authenticated PDF preview and download controls', function () {
    $topic = topicWithPdf();
    $topic->update(['image_path' => 'topics/images/legacy.png']);
    $this->actingAs(User::factory()->create())->get(route('teacher.topics.edit', $topic))
        ->assertOk()->assertSee('Fail PDF')->assertSee('Buang PDF sedia ada')
        ->assertSee('type="application/pdf"', false)->assertSee(route('topics.pdf', $topic))
        ->assertDontSee('name="image_file"', false);

    $student = User::factory()->student()->active()->create(['academic_year_id' => AcademicYear::factory()->active()]);
    $this->actingAs($student)->get(route('student.learning.show', $topic))
        ->assertOk()->assertSee('Nota Pembelajaran (PDF)')->assertSee('Buka Tab Baharu')->assertSee('Muat Turun')
        ->assertSee('type="application/pdf"', false)->assertSee(route('topics.pdf', $topic))
        ->assertDontSee('/storage/topics/documents/')->assertDontSee('legacy.png');
});

test('PDFs are served inline or downloaded and support byte ranges', function () {
    $topic = topicWithPdf();
    $student = User::factory()->student()->active()->create(['academic_year_id' => AcademicYear::factory()->active()]);
    $this->actingAs($student)->get(route('topics.pdf', $topic))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=nota-topik-'.$topic->topic_order.'.pdf')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('topics.pdf', ['topic' => $topic, 'download' => 1]))
        ->assertOk()->assertDownload('nota-topik-'.$topic->topic_order.'.pdf');
    $this->get(route('topics.pdf', $topic), ['Range' => 'bytes=0-9'])
        ->assertStatus(206)->assertHeader('Content-Length', '10');
});

test('guests cannot access learning PDFs', function () {
    $this->get(route('topics.pdf', topicWithPdf()))->assertRedirect(route('login'));
});

test('inactive accounts cannot preview or download PDFs', function () {
    $topic = topicWithPdf();
    $user = User::factory()->create(['status' => UserStatus::Inactive]);
    $this->actingAs($user)->get(route('topics.pdf', $topic))->assertRedirect(route('login'));
    $this->assertGuest();
    $this->get(route('topics.pdf', ['topic' => $topic, 'download' => 1]))->assertRedirect(route('login'));
});

test('students outside the active academic year cannot access PDFs', function (AcademicYearStatus $status) {
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => AcademicYear::factory()->create(['status' => $status]),
    ]);
    $this->actingAs($student)->get(route('topics.pdf', topicWithPdf()))->assertRedirect(route('login'));
    $this->assertGuest();
})->with([AcademicYearStatus::Upcoming, AcademicYearStatus::Closed]);

test('only teachers can preview unpublished PDFs', function (TopicStatus $status) {
    $topic = topicWithPdf($status);
    $student = User::factory()->student()->active()->create(['academic_year_id' => AcademicYear::factory()->active()]);
    $this->actingAs($student)->get(route('topics.pdf', $topic))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('topics.pdf', $topic))->assertOk();
})->with([TopicStatus::Draft, TopicStatus::Archived]);

test('missing PDF files return not found', function () {
    $topic = topicWithPdf();
    Storage::disk('local')->delete($topic->pdf_path);
    $this->actingAs(User::factory()->create())->get(route('topics.pdf', $topic))->assertNotFound();
    $topic->update(['pdf_path' => null]);
    $this->get(route('topics.pdf', $topic))->assertNotFound();
});

test('students cannot upload or replace learning PDFs', function () {
    $topic = topicWithPdf();
    $student = User::factory()->student()->active()->create(['academic_year_id' => AcademicYear::factory()->active()]);
    $this->actingAs($student)->patch(route('teacher.topics.update', $topic), [
        'pdf_file' => topicPdfUpload(), 'status' => 'published',
    ])->assertForbidden();
    expect($topic->refresh()->pdf_path)->toBe('topics/documents/nota.pdf');
});
