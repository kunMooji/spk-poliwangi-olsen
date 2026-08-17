<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentPriority;
use App\Models\Period;
use App\Models\StudyProgram;
use App\Support\Riasec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
    /**
     * Gelombang yang sedang disaring; null berarti seluruh gelombang.
     *
     * Disimpan sebagai properti karena hampir setiap metrik di bawah perlu
     * menyaring dengan nilai yang sama.
     */
    private ?string $periodId = null;

    public function __invoke(Request $request): View
    {
        $this->periodId = $request->input('period') ?: null;

        $totalCompleted = $this->base()->count();

        return view('admin.statistics', [
            'totalCompleted' => $totalCompleted,
            'periods' => Period::query()->orderByDesc('starts_at')->orderByDesc('id')->get(),
            'selectedPeriod' => $this->periodId,
            'schools' => $this->topSchools(),
            'majors' => $this->distribution('school_major'),
            'genders' => $this->distribution('gender'),
            'monthly' => $this->monthlyTrend(),
            'subjectAverages' => $this->subjectAverages(),
            'interestGap' => $this->interestGap(),
            'dominantDistribution' => $this->distribution('dominant_type'),
            'dimensionLabels' => Riasec::LABELS,
            'raporAverage' => round((float) $this->base()->avg('rapor_average'), 2),
            'averageFit' => round((float) $this->base()->avg('recommended_k_normal'), 2),
            'matchRatio' => $totalCompleted > 0
                ? round($this->base()->where('matches_preference', true)->count() / $totalCompleted * 100, 1)
                : 0.0,
        ]);
    }

    /**
     * Titik awal seluruh metrik: tes selesai, dipersempit gelombang bila dipilih.
     *
     * @return Builder<Assessment>
     */
    private function base(): Builder
    {
        return Assessment::query()
            ->completed()
            ->when($this->periodId, fn (Builder $query, string $period) => $period === 'none'
                ? $query->whereNull('period_id')
                : $query->where('period_id', $period));
    }

    /**
     * @return Collection<int, object>
     */
    private function topSchools(): Collection
    {
        return $this->base()
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
        return $this->base()
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
        return $this->base()
            ->where('completed_at', '>=', now()->subYear()->startOfMonth())
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as period, count(*) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');
    }

    /**
     * Rata-rata nilai pendaftar pada tiap mata pelajaran pendukung.
     *
     * Baris bernilai null — mapel yang tidak ditempuh responden — tidak ikut
     * dirata-ratakan, sehingga angkanya mencerminkan pendaftar yang benar-benar
     * menempuh mapel tersebut, bukan tercampur nilai nol semu.
     *
     * @return array<string, float>
     */
    private function subjectAverages(): array
    {
        return Assessment::query()
            ->whereIn('assessments.id', $this->base()->select('assessments.id'))
            ->join('assessment_subject_scores', 'assessment_subject_scores.assessment_id', '=', 'assessments.id')
            ->join('subjects', 'subjects.id', '=', 'assessment_subject_scores.subject_id')
            ->whereNotNull('assessment_subject_scores.score')
            ->groupBy('subjects.id', 'subjects.name', 'subjects.sort_order')
            ->orderBy('subjects.sort_order')
            ->pluck(DB::raw('ROUND(AVG(assessment_subject_scores.score), 2) as average'), 'subjects.name')
            ->map(fn ($average) => (float) $average)
            ->all();
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
        // Sesi tes yang masuk hitungan disamakan dengan metrik lain agar
        // "diminati" dan "direkomendasikan" benar-benar sebanding.
        $scopedIds = $this->base()->select('id');

        $chosen = AssessmentPriority::query()
            ->where('priority_order', 1)
            ->whereIn('assessment_id', $scopedIds)
            ->selectRaw('study_program_id, count(*) as total')
            ->groupBy('study_program_id')
            ->pluck('total', 'study_program_id');

        $recommended = $this->base()
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
