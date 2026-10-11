<?php

use App\Enums\TopicStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function createStudentForLearningModule(): User
{
    $academicYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();

    return User::factory()->student()->active()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
    ]);
}

test('only students can access learning modules', function () {
    $topic = Topic::factory()->create([
        'topic_order' => 1,
        'status' => TopicStatus::Published,
    ]);
    $teacher = User::factory()->create();

    $this->get(route('student.dashboard'))
        ->assertRedirect(route('login'));

    $this->actingAs($teacher)
        ->get(route('student.dashboard'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('student.learning.show', $topic))
        ->assertForbidden();
});

test('students only see published learning modules in topic order', function () {
    $student = createStudentForLearningModule();
    $firstPublishedTopic = Topic::factory()->create([
        'topic_order' => 1,
        'topic_name' => 'Benda Hidup',
        'content_text' => 'Kandungan pembelajaran tentang benda hidup.',
        'status' => TopicStatus::Published,
    ]);
    $draftTopic = Topic::factory()->create([
        'topic_order' => 2,
        'topic_name' => 'Jirim Draf',
        'status' => TopicStatus::Draft,
    ]);
    $secondPublishedTopic = Topic::factory()->create([
        'topic_order' => 3,
        'topic_name' => 'Daya',
        'content_text' => 'Kandungan pembelajaran tentang daya.',
        'status' => TopicStatus::Published,
    ]);
    $archivedTopic = Topic::factory()->create([
        'topic_order' => 4,
        'topic_name' => 'Alam Sekitar Arkib',
        'status' => TopicStatus::Archived,
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSeeInOrder([$firstPublishedTopic->topic_name, $secondPublishedTopic->topic_name])
        ->assertDontSee($draftTopic->topic_name)
        ->assertDontSee($archivedTopic->topic_name);
});

test('students can read one published module and view its uploaded media', function () {
    Storage::fake('public');
    Storage::disk('public')->put('topics/images/daya.png', 'image');

    $student = createStudentForLearningModule();
    $topic = Topic::factory()->create([
        'topic_order' => 1,
        'topic_name' => 'Daya',
        'content_text' => 'Daya boleh mengubah pergerakan sesuatu objek.',
        'image_path' => 'topics/images/daya.png',
        'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        'status' => TopicStatus::Published,
    ]);

    $this->actingAs($student)
        ->get(route('student.learning.show', $topic))
        ->assertOk()
        ->assertSee($topic->topic_name)
        ->assertSee($topic->content_text)
        ->assertSee('/storage/topics/images/daya.png')
        ->assertSee('https://www.youtube-nocookie.com/embed/abcdefghijk')
        ->assertSee('Video Pembelajaran');
});

test('students cannot open draft or archived learning modules', function () {
    $student = createStudentForLearningModule();
    $draftTopic = Topic::factory()->create([
        'topic_order' => 1,
        'status' => TopicStatus::Draft,
    ]);
    $archivedTopic = Topic::factory()->create([
        'topic_order' => 2,
        'status' => TopicStatus::Archived,
    ]);

    $this->actingAs($student)
        ->get(route('student.learning.show', $draftTopic))
        ->assertNotFound();

    $this->actingAs($student)
        ->get(route('student.learning.show', $archivedTopic))
        ->assertNotFound();
});
