<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentPriority;
use App\Models\StudyProgram;
use App\Support\Riasec;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Statistik agregat untuk kebutuhan institusi, bukan untuk perorangan.
 *
 * Yang dicari di sini bukan "prodi apa untuk si A", melainkan pola: dari sekolah
 * mana calon mahasiswa datang, prodi mana yang diminati tetapi jarang cocok, dan
 * bagaimana persebaran kemampuan pendaftar.
 */
class StatisticsController extends Controller
{
    public function __invoke(): View
    {
        $totalCompleted = Assessment::query()->completed()->count();

        return view('admin.statistics', [
            'totalCompleted' => $totalCompleted,
            'schools' => $this->topSchools(),
            'majors' => $this->distribution('school_major'),
            'genders' => $this->distribution('gender'),
            'monthly' => $this->monthlyTrend(),
            'subjectAverages' => $this->subjectAverages(),
            'interestGap' => $this->interestGap(),
            'dominantDistribution' => $this->distribution('dominant_type'),
            'dimensionLabels' => Riasec::LABELS,
            'subjects' => Riasec::SUBJECTS,
            'averageFit' => round((float) Assessment::query()->completed()->avg('recommended_k_normal'), 2),
            'matchRatio' => $totalCompleted > 0
                ? round(Assessment::query()->completed()->where('matches_preference', true)->count() / $totalCompleted * 100, 1)
                : 0.0,
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function topSchools(): Collection
    {
        return Assessment::query()->completed()
            ->whereNotNull('school_name')
            ->selectRaw('school_name, count(*) as total, avg(recommended_k_normal) as average_fit')
            ->groupBy('school_name')
            ->orderByDesc('total')
            ->take(10)
            ->get();
    }

    /**
     * Sebaran nilai sebuah kolom biodata, terurut dari yang terbanyak.
     *
     * @return Collection<string, int>
     */
    private function distribution(string $column): Collection
    {
        return Assessment::query()->completed()
            ->whereNotNull($column)
            ->selectRaw("{$column} as label, count(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->pluck('total', 'label');
    }

    /**
     * Jumlah tes selesai per bulan selama satu tahun terakhir.
     *
     * @return Collection<string, int>
     */
    private function monthlyTrend(): Collection
    {
        return Assessment::query()->completed()
            ->where('completed_at', '>=', now()->subYear()->startOfMonth())
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as period, count(*) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');
    }

    /**
     * Rata-rata nilai rapor seluruh pendaftar per mata pelajaran.
     *
     * @return array<string, float>
     */
    private function subjectAverages(): array
    {
        $query = Assessment::query()->completed();

        $averages = [];
        foreach (array_keys(Riasec::SUBJECTS) as $subject) {
            $averages[$subject] = round((float) (clone $query)->avg($subject.'_score'), 2);
        }

        return $averages;
    }

    /**
     * Bandingkan seberapa sering sebuah prodi dijadikan pilihan pertama dengan
     * seberapa sering ia benar-benar direkomendasikan sistem.
     *
     * Selisih besar bernilai informatif: prodi yang banyak diminati namun jarang
     * cocok menandakan kesenjangan antara persepsi calon mahasiswa dan profil
     * yang sesungguhnya dituntut prodi tersebut.
     *
     * @return Collection<int, array{program: StudyProgram, chosen: int, recommended: int, gap: int}>
     */
    private function interestGap(): Collection
    {
        $chosen = AssessmentPriority::query()
            ->where('priority_order', 1)
            ->whereHas('assessment', fn ($query) => $query->completed())
            ->selectRaw('study_program_id, count(*) as total')
            ->groupBy('study_program_id')
            ->pluck('total', 'study_program_id');

        $recommended = Assessment::query()->completed()
            ->whereNotNull('recommended_program_id')
            ->selectRaw('recommended_program_id, count(*) as total')
            ->groupBy('recommended_program_id')
            ->pluck('total', 'recommended_program_id');

        return StudyProgram::query()
            ->orderBy('name')
            ->get()
            ->map(fn (StudyProgram $program) => [
                'program' => $program,
                'chosen' => (int) ($chosen[$program->id] ?? 0),
                'recommended' => (int) ($recommended[$program->id] ?? 0),
                'gap' => (int) ($chosen[$program->id] ?? 0) - (int) ($recommended[$program->id] ?? 0),
            ])
            ->filter(fn (array $row) => $row['chosen'] > 0 || $row['recommended'] > 0)
            ->sortByDesc(fn (array $row) => abs($row['gap']))
            ->values();
    }
}
