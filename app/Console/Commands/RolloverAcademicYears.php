<?php

namespace App\Console\Commands;

use App\Actions\Teacher\RolloverAcademicYear;
use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\RolloverAudit;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('academic-years:rollover')]
#[Description('Execute due academic-year rollovers without deleting historical records')]
class RolloverAcademicYears extends Command
{
    public function handle(RolloverAcademicYear $rollover): int
    {
        $year = AcademicYear::query()->where('status', AcademicYearStatus::Upcoming)->orderBy('year')->first();
        if ($year === null || $year->rollover_at === null || $year->rollover_at->isFuture()) {
            $this->components->info('Tiada peralihan yang perlu dilaksanakan.');

            return self::SUCCESS;
        }

        try {
            $changed = $rollover->handle($year->academic_year_id);
            $this->components->info($changed ? "Peralihan ke tahun {$year->year} berjaya." : 'Peralihan telah diproses atau jadual telah berubah.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            try {
                RolloverAudit::query()->create([
                    'academic_year_id' => $year->academic_year_id,
                    'performed_by' => $year->scheduled_by,
                    'event' => 'failed',
                    'scheduled_at' => $year->rollover_at,
                    'occurred_at' => now(),
                    'error_type' => class_basename($exception),
                ]);
            } catch (Throwable $auditException) {
                report($auditException);
            }
            $this->components->error('Peralihan gagal. Tiada perubahan separa disimpan. Semak log aplikasi.');

            return self::FAILURE;
        }
    }
}
