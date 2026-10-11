<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the teacher account command creates an active Guru account', function () {
    $this->artisan('spark:create-teacher')
        ->expectsQuestion('Nama penuh guru', 'Cikgu Sains')
        ->expectsQuestion('Nama pengguna', 'cikgu_sains')
        ->expectsQuestion('Kata laluan', 'kata-laluan-kuat')
        ->expectsQuestion('Sahkan kata laluan', 'kata-laluan-kuat')
        ->expectsOutputToContain('Akaun Guru berjaya dicipta.')
        ->assertExitCode(0);

    $teacher = User::query()->where('username', 'cikgu_sains')->firstOrFail();

    expect($teacher->role)->toBe(UserRole::Teacher)
        ->and($teacher->status)->toBe(UserStatus::Active)
        ->and(Hash::check('kata-laluan-kuat', $teacher->password_hash))->toBeTrue();
});
