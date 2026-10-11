<?php

namespace Database\Seeders;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicYear::query()->firstOrCreate(
            ['year' => 2026],
            [
                'status' => AcademicYearStatus::Active,
                'rollover_at' => null,
                'activated_at' => '2025-12-31 16:00:00',
                'closed_at' => null,
            ],
        );

        AcademicYear::query()->firstOrCreate(
            ['year' => 2027],
            [
                'status' => AcademicYearStatus::Upcoming,
                'rollover_at' => null,
                'activated_at' => null,
                'closed_at' => null,
            ],
        );
    }
}
