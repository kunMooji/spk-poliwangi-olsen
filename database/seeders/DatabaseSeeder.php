<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CriteriaSeeder::class,
            RiasecQuestionSeeder::class,
            StudyProgramSeeder::class,
            PeriodSeeder::class,
            UserSeeder::class,
        ]);
    }
}
