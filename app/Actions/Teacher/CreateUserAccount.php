<?php

namespace App\Actions\Teacher;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUserAccount
{
    /**
     * Create a student or class-assigned teacher account from the administrator screen.
     *
     * @param  array{full_name: string, username: string, password: string, role: string, academic_year_id: int|null, kelas_id: int|null, kelas_ids: list<int>}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $role = UserRole::from($data['role']);

            if ($role === UserRole::Teacher) {
                $classIds = Kelas::query()
                    ->whereKey($data['kelas_ids'])
                    ->lockForUpdate()
                    ->pluck('id');

                if ($classIds->count() !== count($data['kelas_ids'])) {
                    throw ValidationException::withMessages([
                        'kelas_ids' => ['Satu atau lebih kelas tidak lagi tersedia.'],
                    ]);
                }

                $teacher = User::query()->create([
                    'full_name' => $data['full_name'],
                    'username' => $data['username'],
                    'password_hash' => $data['password'],
                    'role' => UserRole::Teacher,
                    'status' => UserStatus::Active,
                ]);

                $teacher->assignedClasses()->sync($classIds);

                return $teacher;
            }

            if ($data['academic_year_id'] === null || $data['kelas_id'] === null) {
                throw ValidationException::withMessages([
                    'academic_year_id' => 'Tahun akademik dan kelas diperlukan untuk akaun pelajar.',
                ]);
            }

            $academicYear = AcademicYear::query()
                ->orderBy('academic_year_id')->lockForUpdate()->get()
                ->firstWhere('academic_year_id', $data['academic_year_id']);

            if ($academicYear === null || $academicYear->status === AcademicYearStatus::Closed) {
                throw ValidationException::withMessages(['academic_year_id' => 'Tahun akademik ini telah ditutup.']);
            }

            if (! Kelas::query()->whereKey($data['kelas_id'])->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['kelas_id' => 'Kelas tidak lagi tersedia. Sila pilih kelas yang lain.']);
            }

            $level = Level::query()
                ->orderBy('minimum_points')
                ->orderBy('level_number')
                ->firstOrFail();

            return User::query()->create([
                'academic_year_id' => $academicYear->academic_year_id,
                'current_level_id' => $level->level_id,
                'full_name' => $data['full_name'],
                'username' => $data['username'],
                'password_hash' => $data['password'],
                'role' => UserRole::Student,
                'kelas_id' => $data['kelas_id'],
                'status' => $academicYear->status === AcademicYearStatus::Active
                    ? UserStatus::Active
                    : UserStatus::Approved,
            ]);
        });
    }
}
