<?php

namespace App\Actions\Auth;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterStudent
{
    /**
     * Create a pending student account for the upcoming academic year.
     */
    public function handle(string $fullName, string $username, int $kelasId, string $password): User
    {
        return DB::transaction(function () use ($fullName, $username, $kelasId, $password): User {
            $academicYear = AcademicYear::query()
                ->orderBy('academic_year_id')->lockForUpdate()->get()
                ->where('status', AcademicYearStatus::Upcoming)
                ->sortBy('year')
                ->first();

            if (! $academicYear instanceof AcademicYear) {
                throw ValidationException::withMessages([
                    'registration' => ['Pendaftaran pelajar belum dibuka. Sila hubungi guru.'],
                ]);
            }

            if (! Kelas::query()->whereKey($kelasId)->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['kelas_id' => 'Kelas tidak lagi tersedia. Sila pilih kelas yang lain.']);
            }

            $initialLevel = Level::query()
                ->where('level_number', 1)
                ->firstOrFail();

            return User::query()->create([
                'academic_year_id' => $academicYear->academic_year_id,
                'current_level_id' => $initialLevel->level_id,
                'full_name' => $fullName,
                'username' => $username,
                'password_hash' => $password,
                'role' => UserRole::Student,
                'kelas_id' => $kelasId,
                'status' => UserStatus::Pending,
            ]);
        });
    }
}
