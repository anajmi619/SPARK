<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated teachers are directed to the teacher dashboard', function () {
    $teacher = User::factory()->create();

    $response = $this->actingAs($teacher)->get(route('dashboard'));

    $response->assertRedirect(route('teacher.dashboard', absolute: false));
});

test('users cannot visit the dashboard for a different role', function () {
    $teacher = User::factory()->teacher()->create();

    $response = $this->actingAs($teacher)->get(route('student.dashboard'));

    $response->assertForbidden();
});
