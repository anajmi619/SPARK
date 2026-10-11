<?php

use App\Models\Kelas;
use App\Models\User;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\LevelSeeder;

beforeEach(function () {
    $this->seed([AcademicYearSeeder::class, LevelSeeder::class]);
});

test('student registration validates a unique username and a confirmed password', function () {
    User::factory()->create(['username' => 'pelajar_satu']);

    $response = $this->from(route('register'))->post(route('register.store'), [
        'full_name' => 'Pelajar Satu',
        'username' => 'pelajar_satu',
        'kelas_id' => Kelas::query()->where('nama', 'Ibnu Sina')->sole()->id,
        'password' => 'password',
        'password_confirmation' => 'berbeza',
    ]);

    $response->assertRedirect(route('register', absolute: false))
        ->assertSessionHasErrors(['username', 'password']);
});
