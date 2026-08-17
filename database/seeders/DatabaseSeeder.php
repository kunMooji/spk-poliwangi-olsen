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
            // Mapel harus ada sebelum prodi, karena prodi merujuknya sebagai mapel pendukung.
            SubjectSeeder::class,
            StudyProgramSeeder::class,
            PeriodSeeder::class,
            UserSeeder::class,
        ]);
    }
}
