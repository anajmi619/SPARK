<?php

namespace App\Actions\Teacher;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\RolloverAudit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageAcademicYears
{
    public function create(int $year): AcademicYear
    {
        return DB::transaction(function () use ($year): AcademicYear {
            $years = AcademicYear::query()->orderBy('academic_year_id')->lockForUpdate()->get();
            $latest = $years->max('year');
            if ($latest === null || $year !== $latest + 1) {
                throw ValidationException::withMessages(['year' => 'Tambah tahun akademik seterusnya mengikut turutan.']);
            }

            return AcademicYear::query()->create(['year' => $year, 'status' => AcademicYearStatus::Upcoming]);
        }, 3);
    }

    public function schedule(AcademicYear $year, ?CarbonImmutable $date, User $teacher): void
    {
        DB::transaction(function () use ($year, $date, $teacher): void {
            $years = AcademicYear::query()->orderBy('academic_year_id')->lockForUpdate()->get();
            $target = $years->firstWhere('academic_year_id', $year->academic_year_id);
            $current = $years->firstWhere('status', AcademicYearStatus::Active);
            $next = $years->where('status', AcademicYearStatus::Upcoming)->sortBy('year')->first();

            if ($target === null || $current === null || $next === null || ! $target->is($next) || $target->year <= $current->year) {
                throw ValidationException::withMessages(['rollover_at' => 'Hanya tahun akan datang yang terdekat boleh dijadualkan.']);
            }
            if ($date !== null && $date->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
                throw ValidationException::withMessages(['rollover_at' => 'Pilih tarikh dan masa selepas waktu semasa.']);
            }
            if ($date === null && $target->rollover_at === null) {
                return;
            }

            RolloverAudit::query()->create([
                'academic_year_id' => $target->academic_year_id,
                'previous_year_id' => $current->academic_year_id,
                'performed_by' => $teacher->user_id,
                'event' => $date === null ? 'cancelled' : 'scheduled',
                'scheduled_at' => $date ?? $target->rollover_at,
                'occurred_at' => now(),
            ]);
            $target->update(['rollover_at' => $date, 'scheduled_by' => $teacher->user_id]);
        }, 3);
    }
}
