<?php

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('spark:create-admin {--name=} {--username=}')]
#[Description('Create an active SPARK administrator account from the local console')]
class CreateAdminAccount extends Command
{
    use ProfileValidationRules;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fullName = $this->option('name') ?: $this->ask('Nama penuh pentadbir');
        $username = $this->option('username') ?: $this->ask('Nama pengguna');
        $password = $this->secret('Kata laluan');
        $passwordConfirmation = $this->secret('Sahkan kata laluan');

        if ($password !== $passwordConfirmation) {
            $this->components->error('Pengesahan kata laluan tidak sepadan.');

            return self::INVALID;
        }

        $validated = Validator::make([
            'full_name' => $fullName,
            'username' => $username,
            'password' => $password,
        ], [
            ...$this->profileRules(),
            'password' => ['required', 'string', Password::default()],
        ])->validate();

        User::query()->create([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'password_hash' => $validated['password'],
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->components->info('Akaun Pentadbir berjaya dicipta.');

        return self::SUCCESS;
    }
}
