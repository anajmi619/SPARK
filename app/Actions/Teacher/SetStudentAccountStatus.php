<?php

namespace App\Actions\Teacher;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetStudentAccountStatus
{
    /**
     * Activate or deactivate a student in the current academic year.
     */
    public function handle(User $student, UserStatus $requestedStatus): void
    {
        DB::transaction(function () use ($student, $requestedStatus): void {
            AcademicYear::query()->orderBy('academic_year_id')->lockForUpdate()->get();
            $student = User::query()->whereKey($student->user_id)->lockForUpdate()->firstOrFail();
            $student->loadMissing('academicYear');

            if ($student->role !== UserRole::Student) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya akaun pelajar boleh dikemas kini.'],
                ]);
            }

            if ($student->academicYear?->status !== AcademicYearStatus::Active) {
                throw ValidationException::withMessages([
                    'status' => ['Status akaun hanya boleh diubah untuk tahun akademik yang aktif.'],
                ]);
            }

            if ($requestedStatus === UserStatus::Active && $student->status !== UserStatus::Inactive) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya akaun pelajar yang tidak aktif boleh diaktifkan semula.'],
                ]);
            }

            if ($requestedStatus === UserStatus::Inactive && $student->status !== UserStatus::Active) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya akaun pelajar yang aktif boleh dinyahaktifkan.'],
                ]);
            }

            $student->update(['status' => $requestedStatus]);
        });
    }
}
