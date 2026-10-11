<?php

namespace App\Actions\Teacher;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetManagedAccountPassword
{
    public function handle(User $account, string $password): void
    {
        if (! in_array($account->role, [UserRole::Student, UserRole::Teacher], true)) {
            throw ValidationException::withMessages([
                'password' => ['Kata laluan pentadbir tidak boleh ditetapkan semula oleh pentadbir lain.'],
            ]);
        }

        DB::transaction(function () use ($account, $password): void {
            $account->update([
                'password_hash' => $password,
                'recovery_requested_at' => null,
            ]);

            DB::table('sessions')->where('user_id', $account->user_id)->delete();
        });
    }
}
