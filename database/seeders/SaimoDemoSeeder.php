<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\RiasecQuestion;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Data uji: calon mahasiswa dengan nilai rapor dan minat RIASEC condong ke
 * seni/bahasa, tetapi memilih tiga prodi IT sebagai prioritas. Dipakai untuk
 * mengamati perilaku rekomendasi ketika prioritas pilihan tidak sejalan
 * dengan profil akademik dan minatnya.
 */
class SaimoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'saimo@gmail.com'],
            [
                'name' => 'Saimo',
                'password' => 'password',
                'role' => User::ROLE_MAHASISWA,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $priorityCodes = ['TRPL-D4', 'TI-D3', 'BD-D4'];
        $programs = StudyProgram::query()->whereIn('code', $priorityCodes)->get()->keyBy('code');

        foreach ($priorityCodes as $code) {
            if (! $programs->has($code)) {
                throw new RuntimeException("Program studi {$code} tidak ditemukan. Jalankan StudyProgramSeeder terlebih dahulu.");
            }
        }

        $assessment = Assessment::query()->updateOrCreate(
            ['user_id' => $user->id, 'code' => 'TES-SAIMO01'],
            [
                'full_name' => 'Saimo',
                'gender' => 'P',
                'school_name' => 'SMA Negeri 1 Banyuwangi',
                'school_major' => 'Bahasa',
                'graduation_year' => 2026,
                'phone' => '081234567890',
                // Nilai rapor condong ke bahasa, lemah di sains/matematika.
                'math_score' => 45,
                'physics_score' => 42,
                'chemistry_score' => 40,
                'biology_score' => 48,
                'indonesian_score' => 92,
                'english_score' => 90,
                'status' => 'questionnaire',
            ]
        );

        $assessment->priorities()->delete();
        foreach (array_values($priorityCodes) as $index => $code) {
            $assessment->priorities()->create([
                'study_program_id' => $programs[$code]->id,
                'priority_order' => $index + 1,
            ]);
        }

        // Jawaban RIASEC condong Artistic (kuat) dan Social (sedang), sisanya lemah
        // -- mensimulasikan profil minat seni/bahasa, bukan minat teknis/IT.
        $dimensionScore = [
            'A' => 5,
            'S' => 4,
            'R' => 2,
            'I' => 2,
            'E' => 2,
            'C' => 2,
        ];

        $assessment->answers()->delete();
        $now = now();
        $rows = [];

        foreach (RiasecQuestion::query()->active()->get() as $question) {
            $rows[] = [
                'assessment_id' => $assessment->id,
                'riasec_question_id' => $question->id,
                'dimension' => $question->dimension,
                'score' => $dimensionScore[$question->dimension] ?? 3,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $assessment->answers()->insert($rows);

        app(RecommendationService::class)->calculate($assessment->fresh(['priorities', 'answers']));

        $this->command?->info("Assessment {$assessment->code} untuk {$user->email} selesai dihitung.");
    }
}
