<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('Peranan')
        ->assertSee('Pelajar')
        ->assertSee('Guru');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
        'role' => 'teacher',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('teacher login selection accepts both teachers and administrators', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
        'role' => 'teacher',
    ])->assertRedirect(route('teacher.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
})->with([UserRole::Teacher, UserRole::Admin]);

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
        'role' => 'teacher',
    ]);

    $response->assertSessionHasErrorsIn('username');

    $this->assertGuest();
});

test('active students in the active academic year can authenticate', function () {
    $academicYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create(['level_number' => 1]);
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
    ]);

    $response = $this->post(route('login.store'), [
        'username' => $student->username,
        'password' => 'password',
        'role' => 'student',
    ]);

    $response->assertRedirect(route('student.dashboard', absolute: false));
    $this->assertAuthenticatedAs($student);
});

test('login rejects a role mismatch, a pending account, and a student in a non-active year', function () {
    $teacher = User::factory()->create();
    $pendingStudent = User::factory()->student()->create();
    $upcomingStudent = User::factory()->student()->active()->create([
        'current_level_id' => $pendingStudent->current_level_id,
    ]);

    foreach ([
        [$teacher, 'student'],
        [$pendingStudent, 'student'],
        [$upcomingStudent, 'student'],
    ] as [$user, $role]) {
        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
            'role' => $role,
        ]);

        $response->assertSessionHasErrorsIn('username');
        $this->assertGuest();
    }
});

test('a role must be selected before login', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertGuest();
});

test('unsupported authentication features are disabled', function () {
    expect(config('fortify.features'))->toBe([]);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
