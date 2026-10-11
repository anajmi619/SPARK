<?php

namespace App\Actions\Teacher;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\RolloverAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RolloverAcademicYear
{
    public function handle(int $yearId): bool
    {
        return DB::transaction(function () use ($yearId): bool {
            // Lock years before accounts, as in registration and approval.
            $years = AcademicYear::query()->orderBy('academic_year_id')->lockForUpdate()->get();
            $target = $years->firstWhere('academic_year_id', $yearId);
            if ($target === null || $target->status !== AcademicYearStatus::Upcoming
                || $target->rollover_at === null || $target->rollover_at->isFuture()) {
                return false;
            }
            $current = $years->firstWhere('status', AcademicYearStatus::Active);
            $next = $years->where('status', AcademicYearStatus::Upcoming)->sortBy('year')->first();
            if ($current === null || $next === null || ! $target->is($next) || $target->year <= $current->year) {
                throw new RuntimeException('Turutan tahun akademik tidak sah.');
            }

            $deactivated = User::query()->where('role', UserRole::Student)
                ->where('academic_year_id', $current->academic_year_id)->where('status', UserStatus::Active)
                ->update(['status' => UserStatus::Inactive, 'remember_token' => null]);
            $current->update(['status' => AcademicYearStatus::Closed, 'closed_at' => now()]);
            $target->update(['status' => AcademicYearStatus::Active, 'activated_at' => now()]);
            $activated = User::query()->where('role', UserRole::Student)
                ->where('academic_year_id', $target->academic_year_id)->where('status', UserStatus::Approved)
                ->update(['status' => UserStatus::Active]);

            RolloverAudit::query()->create([
                'academic_year_id' => $target->academic_year_id,
                'previous_year_id' => $current->academic_year_id,
                'performed_by' => $target->scheduled_by,
                'event' => 'completed',
                'scheduled_at' => $target->rollover_at,
                'occurred_at' => now(),
                'deactivated_count' => $deactivated,
                'activated_count' => $activated,
            ]);

            return true;
        }, 3);
    }
}
