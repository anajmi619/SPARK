<?php

namespace App\Console\Commands;

use App\Actions\Student\CalculateStudentProgress;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('spark:sync-levels')]
#[Description('Recalculate student levels from their best completed quiz scores')]
class SyncStudentLevels extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CalculateStudentProgress $calculateStudentProgress): int
    {
        $count = 0;
        foreach (User::query()->where('role', UserRole::Student->value)->lazyById(100, 'user_id') as $student) {
            $calculateStudentProgress->synchronize($student);
            $count++;
        }

        $this->components->info("Tahap {$count} pelajar telah diselaraskan.");

        return self::SUCCESS;
    }
}
