<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AcademicYearSeeder::class,
            LevelSeeder::class,
            KelasSeeder::class,
            TopicSeeder::class,
            ScientificSkillsTopicSeeder::class,
            HumanTopicSeeder::class,
            MicroorganismTopicSeeder::class,
            LivingInteractionTopicSeeder::class,
        ]);
    }
}
