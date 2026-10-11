<?php

namespace App\Actions\Teacher;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetStudentPassword
{
    /**
     * Set a student password and end their existing sessions.
     */
    public function handle(User $student, string $password): void
    {
        if ($student->role !== UserRole::Student) {
            throw ValidationException::withMessages([
                'password' => ['Kata laluan hanya boleh ditetapkan semula untuk akaun pelajar.'],
            ]);
        }

        DB::transaction(function () use ($student, $password): void {
            $student->update([
                'password_hash' => $password,
                'recovery_requested_at' => null,
            ]);

            DB::table('sessions')->where('user_id', $student->user_id)->delete();
        });
    }
}
