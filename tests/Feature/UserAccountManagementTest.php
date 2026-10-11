<?php

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withSession(['auth.password_confirmed_at' => time()]);
});

function createManagedStudent(AcademicYear $academicYear, Level $level, UserStatus $status, array $attributes = []): User
{
    return User::factory()->student()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
        'status' => $status,
        ...$attributes,
    ]);
}

test('only teachers can access user account management', function () {
    $activeYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();
    $student = createManagedStudent($activeYear, $level, UserStatus::Active);

    $this->get(route('teacher.users.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($student)
        ->get(route('teacher.users.index'))
        ->assertForbidden();
});

test('teachers can filter user accounts by search, academic year, and status', function () {
    $teacher = User::factory()->create();
    $activeYear = AcademicYear::factory()->active()->create(['year' => 2026]);
    $upcomingYear = AcademicYear::factory()->create(['year' => 2027]);
    $level = Level::factory()->create();
    $activeStudent = createManagedStudent($activeYear, $level, UserStatus::Active, [
        'full_name' => 'Aiman Hakim',
        'username' => 'aiman_hakim',
    ]);
    $pendingStudent = createManagedStudent($upcomingYear, $level, UserStatus::Pending, [
        'full_name' => 'Siti Mariam',
        'username' => 'siti_mariam',
    ]);

    $this->actingAs($teacher)
        ->get(route('teacher.users.index', [
            'search' => 'aiman',
            'academic_year_id' => $activeYear->academic_year_id,
            'status' => UserStatus::Active->value,
        ]))
        ->assertOk()
        ->assertSee($activeStudent->full_name)
        ->assertDontSee($pendingStudent->full_name);
});

test('teachers can access the create user form from user account management', function () {
    $teacher = User::factory()->create();
    AcademicYear::factory()->active()->create(['year' => 2026]);

    $this->actingAs($teacher)
        ->get(route('teacher.users.index'))
        ->assertOk()
        ->assertSee('Tambah Pengguna')
        ->assertSee(route('teacher.users.create', absolute: false));

    $this->actingAs($teacher)
        ->get(route('teacher.users.create'))
        ->assertOk()
        ->assertSee('Cipta Akaun')
        ->assertSee('Pelajar')
        ->assertSee('Guru');
});

test('teachers can create a student for the active academic year', function () {
    $teacher = User::factory()->create();
    $activeYear = AcademicYear::factory()->active()->create(['year' => 2026]);
    $level = Level::factory()->create();

    $this->actingAs($teacher)
        ->post(route('teacher.users.store'), [
            'role' => UserRole::Student->value,
            'full_name' => 'Nur Aina',
            'username' => 'NUR_AINA',
            'academic_year_id' => $activeYear->academic_year_id,
            'kelas_id' => Kelas::query()->where('nama', 'Ibnu Sina')->sole()->id,
            'password' => 'kata-laluan-baharu',
            'password_confirmation' => 'kata-laluan-baharu',
        ])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    $student = User::query()->where('username', 'nur_aina')->firstOrFail();

    expect($student->role)->toBe(UserRole::Student)
        ->and($student->status)->toBe(UserStatus::Active)
        ->and($student->academic_year_id)->toBe($activeYear->academic_year_id)
        ->and($student->current_level_id)->toBe($level->level_id)
        ->and(Hash::check('kata-laluan-baharu', $student->password_hash))->toBeTrue();
});

test('teachers can create an upcoming-year student without allowing early login', function () {
    $teacher = User::factory()->create();
    $upcomingYear = AcademicYear::factory()->create([
        'year' => 2027,
        'status' => AcademicYearStatus::Upcoming,
    ]);
    Level::factory()->create();

    $this->actingAs($teacher)
        ->post(route('teacher.users.store'), [
            'role' => UserRole::Student->value,
            'full_name' => 'Farah Sofea',
            'username' => 'farah_sofea',
            'academic_year_id' => $upcomingYear->academic_year_id,
            'kelas_id' => Kelas::factory()->create(['nama' => 'Al-Biruni'])->id,
            'password' => 'kata-laluan-baharu',
            'password_confirmation' => 'kata-laluan-baharu',
        ])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    $student = User::query()->where('username', 'farah_sofea')->firstOrFail();

    expect($student->status)->toBe(UserStatus::Approved);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'username' => $student->username,
        'password' => 'kata-laluan-baharu',
        'role' => UserRole::Student->value,
    ])->assertSessionHasErrorsIn('username');

    $this->assertGuest();
});

test('teachers can create a teacher account', function () {
    $teacher = User::factory()->create();
    $kelas = Kelas::factory()->create();

    $this->actingAs($teacher)
        ->post(route('teacher.users.store'), [
            'role' => UserRole::Teacher->value,
            'kelas_ids' => [$kelas->id],
            'full_name' => 'Cikgu Sains',
            'username' => 'cikgu_sains_baru',
            'password' => 'kata-laluan-baharu',
            'password_confirmation' => 'kata-laluan-baharu',
        ])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    $newTeacher = User::query()->where('username', 'cikgu_sains_baru')->firstOrFail();

    expect($newTeacher->role)->toBe(UserRole::Teacher)
        ->and($newTeacher->status)->toBe(UserStatus::Active)
        ->and($newTeacher->academic_year_id)->toBeNull()
        ->and($newTeacher->kelas_id)->toBeNull()
        ->and($newTeacher->assignedClasses->modelKeys())->toBe([$kelas->id])
        ->and(Hash::check('kata-laluan-baharu', $newTeacher->password_hash))->toBeTrue();
});

test('students cannot create user accounts', function () {
    $activeYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();
    $student = createManagedStudent($activeYear, $level, UserStatus::Active);

    $this->actingAs($student)
        ->get(route('teacher.users.create'))
        ->assertForbidden();
});

test('approving a pending future-year registration marks it as approved and keeps it unable to log in', function () {
    $teacher = User::factory()->create();
    $upcomingYear = AcademicYear::factory()->create(['status' => AcademicYearStatus::Upcoming]);
    $level = Level::factory()->create();
    $student = createManagedStudent($upcomingYear, $level, UserStatus::Pending);

    $this->actingAs($teacher)
        ->post(route('teacher.users.review'), [
            'user_ids' => [$student->user_id],
            'decision' => 'approve',
        ])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    expect($student->refresh()->status)->toBe(UserStatus::Approved);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'username' => $student->username,
        'password' => 'password',
        'role' => 'student',
    ])->assertSessionHasErrorsIn('username');

    $this->assertGuest();
});

test('approving a pending registration in the active academic year activates the student', function () {
    $teacher = User::factory()->create();
    $activeYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();
    $student = createManagedStudent($activeYear, $level, UserStatus::Pending);

    $this->actingAs($teacher)
        ->post(route('teacher.users.review'), [
            'user_ids' => [$student->user_id],
            'decision' => 'approve',
        ])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    expect($student->refresh()->status)->toBe(UserStatus::Active);
});

test('teachers can reject multiple pending student registrations', function () {
    $teacher = User::factory()->create();
    $upcomingYear = AcademicYear::factory()->create();
    $level = Level::factory()->create();
    $firstStudent = createManagedStudent($upcomingYear, $level, UserStatus::Pending);
    $secondStudent = createManagedStudent($upcomingYear, $level, UserStatus::Pending);

    $this->actingAs($teacher)
        ->post(route('teacher.users.review'), [
            'user_ids' => [$firstStudent->user_id, $secondStudent->user_id],
            'decision' => 'reject',
        ])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    expect($firstStudent->refresh()->status)->toBe(UserStatus::Rejected)
        ->and($secondStudent->refresh()->status)->toBe(UserStatus::Rejected);
});

test('teachers can deactivate and reactivate a student in the active academic year', function () {
    $teacher = User::factory()->create();
    $activeYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();
    $student = createManagedStudent($activeYear, $level, UserStatus::Active);

    $this->actingAs($teacher)
        ->patch(route('teacher.users.status.update', $student), ['status' => UserStatus::Inactive->value])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    expect($student->refresh()->status)->toBe(UserStatus::Inactive);

    $this->actingAs($teacher)
        ->patch(route('teacher.users.status.update', $student), ['status' => UserStatus::Active->value])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    expect($student->refresh()->status)->toBe(UserStatus::Active);
});

test('teachers cannot activate a student from an upcoming academic year', function () {
    $teacher = User::factory()->create();
    $upcomingYear = AcademicYear::factory()->create();
    $level = Level::factory()->create();
    $student = createManagedStudent($upcomingYear, $level, UserStatus::Approved);

    $this->actingAs($teacher)
        ->from(route('teacher.users.index'))
        ->patch(route('teacher.users.status.update', $student), ['status' => UserStatus::Active->value])
        ->assertRedirect(route('teacher.users.index', absolute: false))
        ->assertSessionHasErrors('status');

    expect($student->refresh()->status)->toBe(UserStatus::Approved);
});

test('teachers can reset a student password and clear a recovery request', function () {
    $teacher = User::factory()->create();
    $activeYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create();
    $student = createManagedStudent($activeYear, $level, UserStatus::Active, [
        'recovery_requested_at' => now(),
    ]);

    DB::table('sessions')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $student->user_id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($teacher)
        ->put(route('teacher.users.password.update', $student), [
            'password' => 'baharu-kata-laluan',
            'password_confirmation' => 'baharu-kata-laluan',
        ])
        ->assertRedirect(route('teacher.users.index', absolute: false));

    $student->refresh();

    expect(Hash::check('baharu-kata-laluan', $student->password_hash))->toBeTrue()
        ->and($student->recovery_requested_at)->toBeNull();
    $this->assertDatabaseMissing('sessions', ['user_id' => $student->user_id]);
});

test('administrators can reset teacher passwords but not administrator passwords', function () {
    $teacher = User::factory()->create();
    $otherTeacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('teacher.users.password.edit', $otherTeacher))
        ->assertOk();

    $this->get(route('teacher.users.password.edit', $teacher))->assertNotFound();
});
