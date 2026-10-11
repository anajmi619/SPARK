<?php

use App\Enums\TopicStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\LevelSeeder;

beforeEach(function () {
    $this->seed(LevelSeeder::class);
    $this->year = AcademicYear::factory()->active()->create(['year' => 2026]);
    $this->teacher = User::factory()->create();
});

function reportStudent(AcademicYear $year, string $name, string $class = 'Ibnu Sina', UserStatus $status = UserStatus::Active): User
{
    return User::factory()->student()->create([
        'academic_year_id' => $year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
        'full_name' => $name, 'kelas_id' => Kelas::query()->firstOrCreate(['nama' => $class])->id, 'status' => $status,
    ]);
}

function reportTopic(int $order): Topic
{
    return Topic::factory()->create(['topic_order' => $order, 'topic_name' => 'Topik '.$order, 'status' => TopicStatus::Published]);
}

function reportAttempt(User $student, Topic $topic, int $score, int $number = 1): QuizAttempt
{
    return QuizAttempt::factory()->completed($score)->create([
        'student_id' => $student->user_id, 'topic_id' => $topic->topic_id, 'attempt_number' => $number,
    ]);
}

test('report routes are restricted to active teachers', function (string $routeName) {
    $student = reportStudent($this->year, 'Pelajar A');
    $parameters = $routeName === 'teacher.progress.show' ? ['student' => $student] : [];
    $url = route($routeName, $parameters);
    $this->get($url)->assertRedirect(route('login'));
    $this->actingAs($student)->get($url)->assertForbidden();
    $this->teacher->update(['status' => UserStatus::Inactive]);
    $this->actingAs($this->teacher)->get($url)->assertRedirect(route('login'));
    $this->assertGuest();
    $this->teacher->update(['status' => UserStatus::Active]);
    $this->actingAs($this->teacher)->get($url)->assertOk();
})->with(['teacher.dashboard', 'teacher.progress.index', 'teacher.progress.show', 'teacher.analytics.index']);

test('analytics use best completed scores with equal weight per student and topic', function () {
    $topic = reportTopic(1);
    $secondTopic = reportTopic(2);
    $a = reportStudent($this->year, 'Pelajar A');
    $b = reportStudent($this->year, 'Pelajar B', status: UserStatus::Inactive);
    reportStudent($this->year, 'Belum Menjawab');
    reportAttempt($a, $topic, 5);
    reportAttempt($a, $topic, 8, 2);
    reportAttempt($a, $topic, 6, 3);
    reportAttempt($a, $secondTopic, 6);
    reportAttempt($b, $topic, 0);
    QuizAttempt::factory()->create(['student_id' => $b->user_id, 'topic_id' => $secondTopic->topic_id, 'score' => 10]);
    $pending = reportStudent($this->year, 'Pendaftaran Menunggu', status: UserStatus::Pending);
    reportAttempt($pending, $topic, 10);
    $otherYear = AcademicYear::factory()->create(['year' => 2025, 'status' => 'closed']);
    reportAttempt(reportStudent($otherYear, 'Pelajar Tahun Lalu'), $topic, 10);

    $this->actingAs($this->teacher)->get(route('teacher.analytics.index'))->assertOk()
        ->assertViewHas('summary', fn (array $summary): bool => $summary === [
            'studentCount' => 3, 'activeCount' => 2, 'supportCount' => 1,
            'noResultCount' => 1, 'averageScore' => 46.7, 'completion' => 8.3,
        ])
        ->assertViewHas('topics', fn ($topics): bool => (float) $topics[0]->average_score === 40.0
            && (int) $topics[0]->completed_students === 2 && (int) $topics[0]->weak_students === 1
            && (float) $topics[1]->average_score === 60.0)
        ->assertViewHas('students', fn ($students): bool => $students->total() === 1 && $students[0]->user_id === $b->user_id)
        ->assertSee('Topik Lemah')->assertDontSee('Pendaftaran Menunggu')->assertDontSee('Pelajar Tahun Lalu');

    $this->get(route('teacher.progress.index'))->assertOk()
        ->assertViewHas('students', function ($students) use ($a): bool {
            $row = collect($students->items())->firstWhere('user_id', $a->user_id);

            return (float) $row->average_score === 70.0 && (int) $row->total_points === 140;
        });
});

test('weak topics use a strict sixty percent threshold and improve after retakes', function () {
    $topic = reportTopic(1);
    $weak = reportStudent($this->year, 'Pelajar Lemah');
    $passing = reportStudent($this->year, 'Pelajar Sempadan');
    reportAttempt($weak, $topic, 5);
    reportAttempt($passing, $topic, 6);
    $this->actingAs($this->teacher)->get(route('teacher.progress.index', ['support_only' => 1]))
        ->assertOk()->assertSee('Pelajar Lemah')->assertDontSee('Pelajar Sempadan');

    reportAttempt($weak, $topic, 6, 2);
    $this->get(route('teacher.analytics.index'))->assertOk()
        ->assertViewHas('summary', fn (array $s): bool => $s['supportCount'] === 0 && $s['averageScore'] === 60.0)
        ->assertDontSee('Topik Lemah</span>', false);
});

test('students with no completed quiz are not counted as zero scores or weak learners', function () {
    reportStudent($this->year, 'Pelajar Baru');
    reportTopic(1);
    $this->actingAs($this->teacher)->get(route('teacher.analytics.index'))->assertOk()
        ->assertViewHas('summary', fn (array $s): bool => $s['averageScore'] === null && $s['supportCount'] === 0 && $s['noResultCount'] === 1)
        ->assertViewHas('topics', fn ($topics): bool => $topics[0]->average_score === null)
        ->assertSee('Tiada Keputusan');
});

test('class year and search filters do not leak students from other cohorts', function () {
    $chosen = reportStudent($this->year, 'Pelajar Pilihan');
    reportStudent($this->year, 'Pelajar Kelas Lain', 'Al Farabi');
    $oldYear = AcademicYear::factory()->create(['year' => 2025, 'status' => 'closed']);
    reportStudent($oldYear, 'Pelajar Pilihan Lama');
    foreach ([UserStatus::Pending, UserStatus::Approved, UserStatus::Rejected] as $status) {
        reportStudent($this->year, 'Tidak Layak '.$status->value, status: $status);
    }
    $this->actingAs($this->teacher)->get(route('teacher.progress.index', [
        'academic_year_id' => $this->year->academic_year_id, 'kelas_id' => $chosen->kelas_id, 'search' => 'Pilihan',
    ]))->assertOk()->assertViewHas('students', fn ($s): bool => $s->total() === 1 && $s[0]->user_id === $chosen->user_id);
    $this->get(route('teacher.progress.index', ['search' => $chosen->username]))->assertOk()
        ->assertViewHas('students', fn ($s): bool => $s->total() === 1);
    $this->get(route('teacher.analytics.index', ['kelas_id' => Kelas::factory()->create(['nama' => 'Kelas Kosong'])->id]))->assertOk()
        ->assertViewHas('summary', fn (array $s): bool => $s['studentCount'] === 0 && $s['completion'] === 0.0);
});

test('historical reports preserve inactive students archived topics and stored records', function () {
    $oldYear = AcademicYear::factory()->create(['year' => 2025, 'status' => 'closed']);
    $student = reportStudent($oldYear, 'Pelajar Sejarah', status: UserStatus::Inactive);
    $student->update(['current_level_id' => Level::query()->where('level_number', 7)->sole()->level_id]);
    $topic = reportTopic(1);
    $topic->update(['status' => TopicStatus::Archived]);
    $attempt = reportAttempt($student, $topic, 4);
    $before = [$student->refresh()->getAttributes(), $attempt->refresh()->getAttributes(), $topic->refresh()->getAttributes()];

    $this->actingAs($this->teacher)->get(route('teacher.progress.show', $student))->assertOk()
        ->assertSee('Rekod Tahun 2025 - Baca Sahaja')->assertSee('Diarkibkan')
        ->assertViewHas('progress', fn (array $p): bool => $p['totalPoints'] === 40 && $p['currentLevel']->level_number === 1)
        ->assertSee('Perlu Sokongan')->assertDontSee('Ulang Kuiz');
    $this->get(route('teacher.analytics.index', ['academic_year_id' => $oldYear->academic_year_id]))->assertOk()
        ->assertViewHas('summary', fn (array $s): bool => $s['studentCount'] === 1 && $s['activeCount'] === 0);
    expect([$student->refresh()->getAttributes(), $attempt->refresh()->getAttributes(), $topic->refresh()->getAttributes()])->toBe($before);
});

test('individual reports reject mismatched years classes teachers and unapproved students', function () {
    $student = reportStudent($this->year, 'Pelajar A');
    $otherYear = AcademicYear::factory()->create(['year' => 2027]);
    $pending = reportStudent($this->year, 'Menunggu', status: UserStatus::Pending);
    $this->actingAs($this->teacher)->get(route('teacher.progress.show', ['student' => $student, 'academic_year_id' => $otherYear->academic_year_id]))->assertNotFound();
    $this->get(route('teacher.progress.show', ['student' => $student, 'kelas_id' => Kelas::factory()->create(['nama' => 'Kelas Lain'])->id]))->assertNotFound();
    $this->get(route('teacher.progress.show', $this->teacher))->assertNotFound();
    $this->get(route('teacher.progress.show', $pending))->assertNotFound();
});

test('invalid report filters are rejected', function (array $filter, string $field) {
    $this->actingAs($this->teacher)->getJson(route('teacher.progress.index', $filter))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [['academic_year_id' => 99999], 'academic_year_id'],
    [['kelas_id' => ['bad']], 'kelas_id'],
    [['kelas_id' => 99999], 'kelas_id'],
    [['search' => str_repeat('x', 101)], 'search'],
    [['support_only' => 'yes'], 'support_only'],
    [['page' => 0], 'page'],
]);

test('student list pagination and report links preserve filters', function () {
    for ($i = 1; $i <= 16; $i++) {
        reportStudent($this->year, sprintf('Pelajar %02d', $i));
    }
    $query = ['academic_year_id' => $this->year->academic_year_id, 'kelas_id' => Kelas::query()->where('nama', 'Ibnu Sina')->sole()->id, 'search' => 'Pelajar'];
    $this->actingAs($this->teacher)->get(route('teacher.progress.index', $query))->assertOk()
        ->assertViewHas('students', fn ($s): bool => $s->total() === 16 && $s->count() === 15 && str_contains($s->nextPageUrl(), 'search=Pelajar'))
        ->assertSee('Halaman 1 daripada 2');
    $this->get(route('teacher.progress.index', $query + ['page' => 2]))->assertOk()
        ->assertSee('Pelajar 16')->assertDontSee('Pelajar 01')
        ->assertViewHas('students', fn ($s): bool => $s->count() === 1);
});

test('a fresh installation without academic years renders empty reports', function () {
    $this->year->delete();
    $this->actingAs($this->teacher)->get(route('teacher.dashboard'))->assertOk()
        ->assertSee('Tiada Tahun Akademik')->assertSee('Tiada topik tersedia.')
        ->assertViewHas('summary', fn (array $s): bool => $s['studentCount'] === 0 && $s['averageScore'] === null);
});

test('teacher navigation exposes reports while student navigation does not', function () {
    $this->actingAs($this->teacher)->get(route('teacher.dashboard'))->assertOk()
        ->assertSee(route('teacher.progress.index'), false)->assertSee(route('teacher.analytics.index'), false);
    $this->actingAs(reportStudent($this->year, 'Pelajar A'))->get(route('student.dashboard'))->assertOk()
        ->assertDontSee(route('teacher.progress.index'), false)->assertDontSee(route('teacher.analytics.index'), false);
});

test('all twelve topics can reach full completion and full points', function () {
    $student = reportStudent($this->year, 'Pelajar Lengkap');
    foreach (range(1, 12) as $order) {
        $topic = reportTopic($order);
        reportAttempt($student, $topic, 8);
        reportAttempt($student, $topic, 10, 2);
    }
    $this->actingAs($this->teacher)->get(route('teacher.progress.show', $student))->assertOk()
        ->assertViewHas('summary', fn (array $s): bool => $s['completion'] === 100.0 && $s['averageScore'] === 100.0 && $s['supportCount'] === 0)
        ->assertViewHas('progress', fn (array $p): bool => $p['totalPoints'] === 1200 && $p['currentLevel']->level_number === 7)
        ->assertSee('Juara Sains');
});

test('support counts learners once even with several weak topics and short quizzes stay out of ten', function () {
    $student = reportStudent($this->year, 'Pelajar Memerlukan Bantuan');
    reportAttempt($student, reportTopic(1), 3);
    reportAttempt($student, reportTopic(2), 4);
    $this->actingAs($this->teacher)->get(route('teacher.analytics.index'))->assertOk()
        ->assertViewHas('summary', fn (array $s): bool => $s['supportCount'] === 1 && $s['averageScore'] === 35.0)
        ->assertViewHas('students', fn ($s): bool => $s->total() === 1 && (int) $s[0]->weak_topics === 2 && (int) $s[0]->total_points === 70);
});
