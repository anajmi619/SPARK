<?php

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\User;

test('active students can open only the student dashboard', function () {
    $academicYear = AcademicYear::factory()->active()->create();
    $level = Level::factory()->create(['level_number' => 1]);
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk();

    $this->actingAs($student)
        ->get(route('teacher.dashboard'))
        ->assertForbidden();
});

test('only an active academic year permits student login', function () {
    $academicYear = AcademicYear::factory()->create(['status' => AcademicYearStatus::Upcoming]);
    $level = Level::factory()->create(['level_number' => 1]);
    $student = User::factory()->student()->active()->create([
        'academic_year_id' => $academicYear->academic_year_id,
        'current_level_id' => $level->level_id,
    ]);

    $this->post(route('login.store'), [
        'username' => $student->username,
        'password' => 'password',
        'role' => 'student',
    ])->assertSessionHasErrorsIn('username');

    $this->assertGuest();
});
