<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('full_name', 'Test User')
        ->set('username', 'test_user')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->full_name)->toEqual('Test User');
    expect($user->username)->toEqual('test_user');
});

test('username can remain unchanged when profile is updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('full_name', 'Test User')
        ->set('username', $user->username)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->full_name)->toEqual('Test User');
});

test('profile page does not expose account deletion', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Padam Akaun');
});
