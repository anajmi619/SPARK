<?php

use App\Models\User;

test('password recovery form is available to guests', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Nama Pengguna');
});

test('password recovery records a request for an existing username', function () {
    $user = User::factory()->create(['username' => 'guru_sains']);

    $response = $this->post(route('password.recovery.store'), ['username' => 'guru_sains']);

    $response->assertRedirect(route('login', absolute: false))
        ->assertSessionHas('status');
    expect($user->refresh()->recovery_requested_at)->not->toBeNull();
});

test('password recovery gives the same generic response for an unknown username', function () {
    $response = $this->post(route('password.recovery.store'), ['username' => 'tidak_wujud']);

    $response->assertRedirect(route('login', absolute: false))
        ->assertSessionHas('status', 'Permohonan pemulihan telah diterima. Jika akaun wujud, pentadbir sekolah akan membantu anda.');
});
