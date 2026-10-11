<?php

namespace App\Actions\Teacher;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewStudentRegistrations
{
    /**
     * Approve or reject a group of pending student registrations.
     *
     * @param  array<int, int>  $studentIds
     */
    public function handle(array $studentIds, string $decision): int
    {
        return DB::transaction(function () use ($studentIds, $decision): int {
            AcademicYear::query()->orderBy('academic_year_id')->lockForUpdate()->get();
            $students = User::query()
                ->with('academicYear')
                ->whereIn('user_id', $studentIds)
                ->lockForUpdate()
                ->get();

            if ($students->count() !== count(array_unique($studentIds))
                || $students->contains(fn (User $student): bool => $student->role !== UserRole::Student
                    || $student->status !== UserStatus::Pending
                    || $student->academicYear?->status === AcademicYearStatus::Closed)) {
                throw ValidationException::withMessages([
                    'user_ids' => ['Hanya pendaftaran pelajar yang menunggu boleh disemak.'],
                ]);
            }

            foreach ($students as $student) {
                $status = $decision === 'approve'
                    ? ($student->academicYear?->status === AcademicYearStatus::Active
                        ? UserStatus::Active
                        : UserStatus::Approved)
                    : UserStatus::Rejected;

                $student->update(['status' => $status]);
            }

            return $students->count();
        });
    }
}
