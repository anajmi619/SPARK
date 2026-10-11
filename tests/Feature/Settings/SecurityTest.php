<?php

use App\Livewire\Settings\Security;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

test('security settings page can be rendered without email verification or password confirmation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('security.edit'));

    $response->assertOk();

    $response->assertSee('Kemas Kini Kata Laluan');
    $response->assertDontSeeText('Passkeys');
    $response->assertDontSeeText('Two-factor authentication');
});

test('security settings omit unsupported authentication features', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Kemas Kini Kata Laluan')
        ->assertDontSeeText('Manage your passkeys for passwordless sign-in')
        ->assertDontSeeText('Add a passkey to sign in without a password')
        ->assertDontSeeText('Two-factor authentication');
});

test('users table omits unsupported authentication columns', function () {
    expect(Schema::hasColumns('users', [
        'email',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ]))->toBeFalse();
});

test('password can be updated', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Security::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password_hash))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Security::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});
