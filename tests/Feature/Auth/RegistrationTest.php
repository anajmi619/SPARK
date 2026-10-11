<?php

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\User;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\LevelSeeder;

beforeEach(function () {
    $this->seed([AcademicYearSeeder::class, LevelSeeder::class]);
});

test('student registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertSee('Pendaftaran Pelajar')
        ->assertSee('Kelas');
});

test('a student registration creates a pending account for the upcoming year', function () {
    $response = $this->post(route('register.store'), [
        'full_name' => 'Nur Aina Binti Ali',
        'username' => 'NUR_AINA',
        'kelas_id' => Kelas::query()->where('nama', 'Ibnu Sina')->sole()->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    $student = User::query()->where('username', 'nur_aina')->firstOrFail();

    expect($student->role)->toBe(UserRole::Student)
        ->and($student->status)->toBe(UserStatus::Pending)
        ->and($student->kelas->nama)->toBe('Ibnu Sina')
        ->and($student->academicYear->status)->toBe(AcademicYearStatus::Upcoming)
        ->and($student->currentLevel->level_number)->toBe(1);

    $this->assertGuest();
});

test('student registration fails when no upcoming academic year is available', function () {
    AcademicYear::query()->update(['status' => AcademicYearStatus::Closed]);

    $response = $this->from(route('register'))->post(route('register.store'), [
        'full_name' => 'Nur Aina Binti Ali',
        'username' => 'nur_aina',
        'kelas_id' => Kelas::query()->where('nama', 'Ibnu Sina')->sole()->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('register', absolute: false))
        ->assertSessionHasErrors('registration');
    expect(User::query()->where('username', 'nur_aina')->exists())->toBeFalse();
});
