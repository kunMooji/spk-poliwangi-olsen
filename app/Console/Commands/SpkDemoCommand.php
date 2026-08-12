<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\Criteria;
use App\Models\RiasecQuestion;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\RecommendationService;
use App\Support\Riasec;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Mencetak seluruh tahapan perhitungan CoCoSo ke konsol.
 *
 * Dipakai untuk membandingkan hasil sistem dengan perhitungan manual (Excel)
 * saat penyusunan dan pengujian laporan.
 */
class SpkDemoCommand extends Command
{
    protected $signature = 'spk:demo
                            {assessment? : Kode atau ID sesi tes yang ingin ditampilkan}
                            {--keep : Simpan sesi tes contoh yang dibuat otomatis (default: dibatalkan setelah dicetak)}';

    protected $description = 'Menampilkan tahapan perhitungan RIASEC dan CoCoSo langkah demi langkah';

    public function handle(RecommendationService $recommendation): int
    {
        $identifier = $this->argument('assessment');
        $synthetic = $identifier === null;

        if ($synthetic) {
            DB::beginTransaction();
        }

        try {
            $assessment = $synthetic
                ? $this->makeSampleAssessment()
                : $this->findAssessment($identifier);

            if ($assessment === null) {
                $this->components->error("Sesi tes \"{$identifier}\" tidak ditemukan.");

                return self::FAILURE;
            }

            $calculation = $recommendation->calculate($assessment);
            $assessment->refresh()->load(['priorities.studyProgram', 'recommendedProgram', 'primaryProgram']);

            $this->printProfile($assessment, $synthetic);
            $this->printRiasec($assessment);
            $this->printWeights();
            $this->printMatrix($calculation, 'MATRIKS KEPUTUSAN (x_ij)', 'matrix');
            $this->printBounds($calculation);
            $this->printMatrix($calculation, 'MATRIKS TERNORMALISASI (r_ij)', 'normalized', 6);
            $this->printAggregation($calculation);
            $this->printConclusion($assessment, $calculation);

            return self::SUCCESS;
        } finally {
            if ($synthetic && DB::transactionLevel() > 0) {
                if ($this->option('keep')) {
                    DB::commit();
                    $this->components->info('Sesi tes contoh disimpan ke database.');
                } else {
                    DB::rollBack();
                    $this->components->warn('Sesi tes contoh dibatalkan (rollback). Gunakan --keep bila ingin menyimpannya.');
                }
            }
        }
    }

    private function findAssessment(string $identifier): ?Assessment
    {
        return Assessment::query()
            ->where('code', $identifier)
            ->orWhere('id', is_numeric($identifier) ? (int) $identifier : 0)
            ->first();
    }

    /**
     * Bangun sesi tes contoh yang deterministik agar hasilnya bisa dibandingkan
     * berulang kali dengan perhitungan manual.
     */
    private function makeSampleAssessment(): Assessment
    {
        mt_srand(20260812);

        $user = User::query()->where('role', User::ROLE_MAHASISWA)->first()
            ?? User::query()->firstOrFail();

        $assessment = Assessment::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Contoh Calon Mahasiswa',
            'gender' => 'L',
            'school_name' => 'SMA Negeri 1 Banyuwangi',
            'school_major' => 'IPA',
            'graduation_year' => 2026,
            'math_score' => 88,
            'physics_score' => 82,
            'chemistry_score' => 75,
            'biology_score' => 70,
            'indonesian_score' => 85,
            'english_score' => 90,
            'status' => 'questionnaire',
        ]);

        $programs = StudyProgram::query()->active()->orderBy('code')->take(3)->get();

        foreach ($programs as $index => $program) {
            $assessment->priorities()->create([
                'study_program_id' => $program->id,
                'priority_order' => $index + 1,
            ]);
        }

        // Profil condong ke Investigative dan Conventional agar hasilnya mudah dibaca.
        $bias = ['R' => 3, 'I' => 5, 'A' => 2, 'S' => 3, 'E' => 3, 'C' => 4];

        foreach (RiasecQuestion::query()->active()->ordered()->get() as $question) {
            $base = $bias[$question->dimension] ?? 3;

            $assessment->answers()->create([
                'riasec_question_id' => $question->id,
                'dimension' => $question->dimension,
                'score' => max(1, min(5, $base + mt_rand(-1, 1))),
            ]);
        }

        return $assessment;
    }

    private function printProfile(Assessment $assessment, bool $synthetic): void
    {
        $this->newLine();
        $this->line('<fg=black;bg=cyan> SESI TES </>');
        $this->table([], [
            ['Kode', $assessment->code.($synthetic ? '  (contoh otomatis)' : '')],
            ['Nama', $assessment->full_name],
            ['Asal sekolah', $assessment->school_name ?? '-'],
            ['Nilai rapor', collect($assessment->subjectScores())
                ->map(fn ($score, $subject) => Riasec::subjectLabel($subject).' '.rtrim(rtrim(number_format($score, 2), '0'), '.'))
                ->implode(' | ')],
            ['Prioritas prodi', $assessment->priorities
                ->map(fn ($priority) => $priority->priority_order.'. '.$priority->studyProgram->full_name)
                ->implode(PHP_EOL)],
        ]);
    }

    private function printRiasec(Assessment $assessment): void
    {
        $this->newLine();
        $this->line('<fg=black;bg=cyan> TAHAP 1 — PROFIL RIASEC </>');

        $scores = $assessment->riasecScores();
        $percentages = $assessment->riasecPercentages();

        $rows = [];
        foreach (Riasec::DIMENSIONS as $dimension) {
            $rows[] = [
                $dimension,
                Riasec::name($dimension),
                $scores[$dimension],
                number_format($percentages[$dimension], 2).' %',
            ];
        }

        $this->table(['Dim', 'Nama', 'Skor Likert', 'Persentase'], $rows);
        $this->line("  Kode Holland : <fg=yellow>{$assessment->holland_code}</>  |  Tipe dominan : <fg=yellow>{$assessment->dominant_type}</>");
    }

    private function printWeights(): void
    {
        $this->newLine();
        $this->line('<fg=black;bg=cyan> BOBOT KRITERIA </>');

        $criteria = Criteria::query()->active()->ordered()->get();

        $rows = $criteria->map(fn (Criteria $criterion) => [
            $criterion->code,
            $criterion->name,
            number_format($criterion->weight, 6),
            $criterion->type,
            $criterion->source_label,
        ])->all();

        $rows[] = new TableSeparator;
        $rows[] = ['', 'TOTAL', number_format($criteria->sum('weight'), 6), '', ''];

        $this->table(['Kode', 'Nama', 'Bobot', 'Tipe', 'Sumber'], $rows);
    }

    private function printMatrix(array $calculation, string $title, string $key, int $precision = 4): void
    {
        $this->newLine();
        $this->line("<fg=black;bg=cyan> {$title} </>");

        $programs = $this->programNames($calculation['alternatives']);

        $rows = [];
        foreach ($calculation['alternatives'] as $programId) {
            $row = [$programs[$programId]];

            foreach ($calculation['criteria'] as $code) {
                $row[] = number_format($calculation[$key][$programId][$code], $precision);
            }

            $rows[] = $row;
        }

        $this->table(array_merge(['Alternatif'], $calculation['criteria']), $rows);
    }

    private function printBounds(array $calculation): void
    {
        $this->newLine();
        $this->line('<fg=black;bg=cyan> NILAI MIN & MAKS TIAP KOLOM </>');

        $min = ['min (x_ij)'];
        $max = ['max (x_ij)'];

        foreach ($calculation['criteria'] as $code) {
            $min[] = number_format($calculation['min'][$code], 4);
            $max[] = number_format($calculation['max'][$code], 4);
        }

        $this->table(array_merge([''], $calculation['criteria']), [$min, $max]);
        $this->line('  <fg=gray>Kolom dengan min = maks diberi nilai ternormalisasi 1.0 untuk seluruh alternatif.</>');
        $this->line('  <fg=gray>Nilai ternormalisasi dijaga minimal epsilon = '.$calculation['epsilon'].'.</>');
    }

    private function printAggregation(array $calculation): void
    {
        $this->newLine();
        $this->line('<fg=black;bg=cyan> TAHAP 2-5 — S, P, STRATEGI KOMPROMI, DAN NILAI AKHIR </>');

        $programs = $this->programNames($calculation['alternatives']);

        $ordered = $calculation['alternatives'];
        usort($ordered, fn ($a, $b) => $calculation['ranking'][$a] <=> $calculation['ranking'][$b]);

        $rows = [];
        foreach ($ordered as $programId) {
            $rows[] = [
                $calculation['ranking'][$programId],
                $programs[$programId],
                number_format($calculation['s'][$programId], 6),
                number_format($calculation['p'][$programId], 6),
                number_format($calculation['k_a'][$programId], 6),
                number_format($calculation['k_b'][$programId], 6),
                number_format($calculation['k_c'][$programId], 6),
                '<fg=yellow>'.number_format($calculation['k'][$programId], 6).'</>',
                number_format($calculation['k_normal'][$programId], 2),
            ];
        }

        $this->table(
            ['#', 'Alternatif', 'S_i', 'P_i', 'K_ia', 'K_ib', 'K_ic', 'K_i', 'K (0-100)'],
            $rows
        );
        $this->line('  <fg=gray>λ (lambda) = '.$calculation['lambda'].'</>');
    }

    private function printConclusion(Assessment $assessment, array $calculation): void
    {
        $this->newLine();
        $this->line('<fg=black;bg=cyan> KESIMPULAN </>');

        $threshold = (float) Setting::get('threshold');
        $mode = (string) Setting::get('threshold_mode');
        $scale = $mode === 'raw' ? $calculation['k'] : $calculation['k_normal'];

        $primaryId = $assessment->primary_program_id;
        $primaryValue = $primaryId !== null ? ($scale[$primaryId] ?? null) : null;

        $this->table([], array_filter([
            ['Pilihan utama', $assessment->primaryProgram?->full_name ?? '-'],
            ['Nilai pilihan utama', $primaryValue !== null ? number_format($primaryValue, 4) : '-'],
            ['Ambang batas', number_format($threshold, 4)." (mode: {$mode})"],
            ['Status', $assessment->matches_preference
                ? 'Pilihan utama MEMENUHI ambang batas'
                : 'Pilihan utama TIDAK memenuhi ambang batas — dialihkan ke prodi dengan K tertinggi'],
            ['Rekomendasi', $assessment->recommendedProgram?->full_name ?? '-'],
            ['Nilai K rekomendasi', number_format((float) $assessment->recommended_k_value, 6)
                .'  ('.number_format((float) $assessment->recommended_k_normal, 2).' dari 100)'],
        ]));
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function programNames(array $ids): array
    {
        return StudyProgram::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (StudyProgram $program) => [$program->id => $program->code])
            ->all();
    }
}
