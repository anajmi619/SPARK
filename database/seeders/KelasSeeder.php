<?php

namespace Database\Seeders;

use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ibnu Batutta', 'Ibnu Haitham', 'Ibnu Khaldun', 'Ibnu Qaiyim', 'Ibnu Sina'] as $nama) {
            Kelas::query()->firstOrCreate(['nama' => $nama]);
        }
    }
}
