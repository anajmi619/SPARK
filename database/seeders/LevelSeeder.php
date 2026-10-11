<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            [1, 'Permulaan', 0, 149],
            [2, 'Peneroka Sains', 150, 299],
            [3, 'Penyiasat Sains', 300, 499],
            [4, 'Pakar Kuiz', 500, 699],
            [5, 'Pelajar Konsisten', 700, 899],
            [6, 'Pakar Sains', 900, 1099],
            [7, 'Juara Sains', 1100, 1200],
        ];

        foreach ($levels as [$number, $name, $minimumPoints, $maximumPoints]) {
            Level::query()->updateOrCreate(
                ['level_number' => $number],
                [
                    'level_name' => $name,
                    'minimum_points' => $minimumPoints,
                    'maximum_points' => $maximumPoints,
                ],
            );
        }
    }
}
