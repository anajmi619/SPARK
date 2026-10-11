<?php

use App\Actions\Student\CalculateStudentProgress;
use App\Actions\Student\StartQuizAttempt;
use App\Actions\Teacher\ManageAcademicYears;
use App\Actions\Teacher\ReviewStudentRegistrations;
use App\Actions\Teacher\RolloverAcademicYear;
use App\Enums\AcademicYearStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\RolloverAudit;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\LevelSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-01 00:00:00', 'UTC'));
    $this->withSession(['auth.password_confirmed_at' => now()->timestamp]);
    $this->seed(LevelSeeder::class);
    $this->current = AcademicYear::factory()->active()->create(['year' => 2026]);
    $this->next = AcademicYear::factory()->create(['year' => 2027]);
    $this->teacher = User::factory()->create();
});

function rolloverStudent(AcademicYear $year, UserStatus $status): User
{
    return User::factory()->student()->create([
        'academic_year_id' => $year->academic_year_id,
        'current_level_id' => Level::query()->where('level_number', 1)->sole()->level_id,
        'status' => $status,
    ]);
}

test('only teachers can access academic-year pages and actions', function () {
    $this->get(route('teacher.academic-years.index'))->assertRedirect(route('login'));
    $student = rolloverStudent($this->current, UserStatus::Active);
    $this->actingAs($student)->get(route('teacher.academic-years.index'))->assertForbidden();
    $this->get(route('teacher.academic-years.show', $this->next))->assertForbidden();
    $this->post(route('teacher.academic-years.store'), ['year' => 2028])->assertForbidden();
    $this->patch(route('teacher.academic-years.update', $this->next), [])->assertForbidden();
    $this->delete(route('teacher.academic-years.cancel', $this->next))->assertForbidden();
});

test('teachers can view years preview cohort counts and add the next year', function () {
    rolloverStudent($this->current, UserStatus::Active);
    rolloverStudent($this->next, UserStatus::Approved);
    rolloverStudent($this->next, UserStatus::Pending);
    $this->actingAs($this->teacher)->get(route('teacher.academic-years.index'))
        ->assertOk()->assertSee('Tahun Akademik dan Peralihan')->assertSee('Tambah Tahun 2028');
    $this->get(route('teacher.academic-years.show', $this->next))->assertOk()
        ->assertViewHas('year', fn ($year) => $year->approved_students_count === 1 && $year->pending_students_count === 1)
        ->assertViewHas('current', fn ($year) => $year->active_students_count === 1)
        ->assertSee('Simpan Jadual')->assertSee('Semak Pendaftaran Menunggu');
    $this->post(route('teacher.academic-years.store'), ['year' => 2028])->assertRedirect()->assertSessionHasNoErrors();
    expect(AcademicYear::query()->where('year', 2028)->sole()->status)->toBe(AcademicYearStatus::Upcoming);
    $this->post(route('teacher.academic-years.store'), ['year' => 2028])->assertSessionHasErrors('year');
    $this->post(route('teacher.academic-years.store'), ['year' => 2030])->assertSessionHasErrors('year');
});

test('schedules convert Malaysia time to UTC and record the configuring teacher', function () {
    $this->actingAs($this->teacher)->patch(route('teacher.academic-years.update', $this->next), [
        'rollover_at' => '2027-01-01T00:00', 'confirm' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($this->next->refresh()->rollover_at->format('Y-m-d H:i:s'))->toBe('2026-12-31 16:00:00')
        ->and($this->next->scheduled_by)->toBe($this->teacher->user_id)
        ->and(RolloverAudit::query()->sole()->event)->toBe('scheduled');
    $this->get(route('teacher.academic-years.index'))->assertSee('01/01/2027, 00:00');
    $this->get(route('teacher.academic-years.show', $this->next))->assertSee('2027-01-01T00:00');
    $this->delete(route('teacher.academic-years.cancel', $this->next))->assertRedirect();
    expect($this->next->refresh()->rollover_at)->toBeNull()
        ->and(RolloverAudit::query()->latest('id')->first()->event)->toBe('cancelled');
});

test('scheduling rejects past dates invalid input and missing confirmation', function (array $data, string $field) {
    $this->actingAs($this->teacher)->patch(route('teacher.academic-years.update', $this->next), $data)
        ->assertSessionHasErrors($field);
    expect($this->next->refresh()->rollover_at)->toBeNull();
})->with([
    [['rollover_at' => '2026-11-01T12:00', 'confirm' => 1], 'rollover_at'],
    [['rollover_at' => '2027-02-30T12:00', 'confirm' => 1], 'rollover_at'],
    [['rollover_at' => 'invalid', 'confirm' => 1], 'rollover_at'],
    [['rollover_at' => '2027-01-01T00:00'], 'confirm'],
]);

test('closed active and out-of-order years cannot be scheduled', function () {
    $later = AcademicYear::factory()->create(['year' => 2028]);
    $closed = AcademicYear::factory()->create(['year' => 2025, 'status' => AcademicYearStatus::Closed]);
    foreach ([$later, $closed, $this->current] as $year) {
        $this->actingAs($this->teacher)->patch(route('teacher.academic-years.update', $year), [
            'rollover_at' => '2027-01-01T00:00', 'confirm' => 1,
        ])->assertSessionHasErrors('rollover_at');
    }
});

test('due rollover activates approved students only and preserves historical learning records', function () {
    $old = rolloverStudent($this->current, UserStatus::Active);
    $approved = rolloverStudent($this->next, UserStatus::Approved);
    $pending = rolloverStudent($this->next, UserStatus::Pending);
    $rejected = rolloverStudent($this->next, UserStatus::Rejected);
    $topic = Topic::factory()->create();
    $question = QuizQuestion::factory()->create(['topic_id' => $topic->topic_id]);
    $attempt = QuizAttempt::factory()->completed(8)->create(['student_id' => $old->user_id, 'topic_id' => $topic->topic_id]);
    $answer = QuizAnswer::factory()->incorrectWithFeedback()->create(['attempt_id' => $attempt->attempt_id, 'question_id' => $question->question_id]);
    $attemptBefore = $attempt->refresh()->getAttributes();
    $answerBefore = $answer->refresh()->getAttributes();
    $this->next->update(['rollover_at' => now(), 'scheduled_by' => $this->teacher->user_id]);

    $this->artisan('academic-years:rollover')->assertSuccessful();
    $this->artisan('academic-years:rollover')->assertSuccessful();

    expect($this->current->refresh()->status)->toBe(AcademicYearStatus::Closed)
        ->and($this->next->refresh()->status)->toBe(AcademicYearStatus::Active)
        ->and($old->refresh()->status)->toBe(UserStatus::Inactive)
        ->and($old->academic_year_id)->toBe($this->current->academic_year_id)
        ->and($approved->refresh()->status)->toBe(UserStatus::Active)
        ->and($pending->refresh()->status)->toBe(UserStatus::Pending)
        ->and($rejected->refresh()->status)->toBe(UserStatus::Rejected)
        ->and($this->teacher->refresh()->status)->toBe(UserStatus::Active)
        ->and($attempt->refresh()->getAttributes())->toBe($attemptBefore)
        ->and($answer->refresh()->getAttributes())->toBe($answerBefore)
        ->and(app(CalculateStudentProgress::class)->handle($old)['totalPoints'])->toBe(80)
        ->and(RolloverAudit::query()->where('event', 'completed')->count())->toBe(1)
        ->and(RolloverAudit::query()->sole()->activated_count)->toBe(1)
        ->and(RolloverAudit::query()->sole()->deactivated_count)->toBe(1);
    expect(app(RolloverAcademicYear::class)->handle($this->next->academic_year_id))->toBeFalse();
    $this->actingAs($this->teacher)->get(route('teacher.progress.show', ['student' => $old, 'academic_year_id' => $this->current->academic_year_id]))->assertOk();
});

test('overdue rollover executes on the next run but future and cancelled schedules do not', function () {
    $this->next->update(['rollover_at' => now()->addHour()]);
    $this->artisan('academic-years:rollover')->assertSuccessful();
    expect($this->next->refresh()->status)->toBe(AcademicYearStatus::Upcoming);
    app(ManageAcademicYears::class)->schedule($this->next, null, $this->teacher);
    $this->travel(2)->hours();
    $this->artisan('academic-years:rollover')->assertSuccessful();
    expect($this->next->refresh()->status)->toBe(AcademicYearStatus::Upcoming);
    $this->next->update(['rollover_at' => now()->subDay()]);
    $this->artisan('academic-years:rollover')->assertSuccessful();
    expect($this->next->refresh()->status)->toBe(AcademicYearStatus::Active);
});

test('a failed rollover rolls back all changes and records a retryable failure', function () {
    $student = rolloverStudent($this->current, UserStatus::Active);
    $this->next->update(['rollover_at' => now()]);
    Event::listen('eloquent.creating: '.RolloverAudit::class, function (RolloverAudit $audit): void {
        if ($audit->event === 'completed') {
            throw new RuntimeException('Simulated audit failure');
        }
    });
    $this->artisan('academic-years:rollover')->assertFailed();
    expect($this->current->refresh()->status)->toBe(AcademicYearStatus::Active)
        ->and($this->next->refresh()->status)->toBe(AcademicYearStatus::Upcoming)
        ->and($student->refresh()->status)->toBe(UserStatus::Active)
        ->and(RolloverAudit::query()->sole()->event)->toBe('failed');
    Event::forget('eloquent.creating: '.RolloverAudit::class);
    $this->artisan('academic-years:rollover')->assertSuccessful();
    expect($this->next->refresh()->status)->toBe(AcademicYearStatus::Active);
});

test('students approved after rollover activate immediately while old registrations remain blocked', function () {
    $pending = rolloverStudent($this->next, UserStatus::Pending);
    $oldPending = rolloverStudent($this->current, UserStatus::Pending);
    $this->next->update(['rollover_at' => now()]);
    app(RolloverAcademicYear::class)->handle($this->next->academic_year_id);
    app(ReviewStudentRegistrations::class)->handle([$pending->user_id], 'approve');
    expect($pending->refresh()->status)->toBe(UserStatus::Active);
    expect(fn () => app(ReviewStudentRegistrations::class)->handle([$oldPending->user_id], 'approve'))
        ->toThrow(ValidationException::class);
});

test('existing outgoing sessions cannot access pages or submit quiz answers after rollover', function () {
    $student = rolloverStudent($this->current, UserStatus::Active);
    $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
    $this->next->update(['rollover_at' => now()]);
    app(RolloverAcademicYear::class)->handle($this->next->academic_year_id);
    $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
    $this->actingAs($student)->getJson(route('profile.edit'))->assertForbidden();
    $this->assertGuest();
});

test('the database allows only one active academic year', function () {
    expect(fn () => $this->next->update(['status' => AcademicYearStatus::Active]))->toThrow(QueryException::class);
});

test('login after rollover accepts activated students and rejects old pending and rejected accounts', function () {
    $old = rolloverStudent($this->current, UserStatus::Active);
    $approved = rolloverStudent($this->next, UserStatus::Approved);
    $pending = rolloverStudent($this->next, UserStatus::Pending);
    $rejected = rolloverStudent($this->next, UserStatus::Rejected);
    $this->next->update(['rollover_at' => now()]);
    app(RolloverAcademicYear::class)->handle($this->next->academic_year_id);
    foreach ([$old, $pending, $rejected] as $student) {
        $this->post(route('login.store'), ['username' => $student->username, 'password' => 'password', 'role' => 'student'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }
    $this->post(route('login.store'), ['username' => $approved->username, 'password' => 'password', 'role' => 'student'])
        ->assertRedirect(route('student.dashboard', absolute: false));
    $this->assertAuthenticatedAs($approved);
});

test('an outgoing student cannot start a quiz after losing access even with a stale model', function () {
    $student = rolloverStudent($this->current, UserStatus::Active);
    $topic = Topic::factory()->create(['status' => 'published']);
    $this->next->update(['rollover_at' => now()]);
    app(RolloverAcademicYear::class)->handle($this->next->academic_year_id);
    expect(fn () => app(StartQuizAttempt::class)->handle($topic, $student))
        ->toThrow(HttpException::class);
    expect(QuizAttempt::query()->count())->toBe(0);
});

test('running academic-year seeds again preserves completed rollover and schedules', function () {
    $this->next->update(['rollover_at' => now()]);
    app(RolloverAcademicYear::class)->handle($this->next->academic_year_id);
    $this->seed(AcademicYearSeeder::class);
    expect($this->current->refresh()->status)->toBe(AcademicYearStatus::Closed)
        ->and($this->next->refresh()->status)->toBe(AcademicYearStatus::Active)
        ->and($this->next->rollover_at)->not->toBeNull();
});

test('the scheduler runs the rollover command every minute without overlapping', function () {
    $events = collect(app(Schedule::class)->events());
    $event = $events->first(fn ($event) => str_contains($event->command ?? '', 'academic-years:rollover'));
    expect($event)->not->toBeNull()->and($event->expression)->toBe('* * * * *')->and($event->withoutOverlapping)->toBeTrue();
});
