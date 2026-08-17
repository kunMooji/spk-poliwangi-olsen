<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\RiasecQuestion;
use App\Models\StudyProgram;
use App\Models\Subject;
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
                // Rerata rapor sedang, tetapi profil mapelnya condong ke bahasa dan
                // sosial serta lemah di sains — inilah yang menggerakkan C2.
                'rapor_average' => 68.40,
                'status' => 'questionnaire',
            ]
        );

        $assessment->raporSemesters()->delete();
        foreach ([1 => 66, 2 => 67, 3 => 68, 4 => 70, 5 => 71] as $semester => $average) {
            $assessment->raporSemesters()->create([
                'semester' => $semester,
                'average_score' => $average,
            ]);
        }

        $assessment->subjectScores()->delete();
        $subjectScores = [
            'matematika' => 45,
            'fisika' => 42,
            'kimia' => 40,
            'biologi' => 48,
            'informatika' => 52,
            'bahasa-inggris' => 90,
            'ekonomi-akuntansi' => 84,
            'geografi' => 86,
        ];

        foreach (Subject::query()->whereIn('code', array_keys($subjectScores))->get() as $subject) {
            $assessment->subjectScores()->create([
                'subject_id' => $subject->id,
                'score' => $subjectScores[$subject->code],
            ]);
        }

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
