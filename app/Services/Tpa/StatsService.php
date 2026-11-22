<?php

namespace App\Services\Tpa;

use App\Models\TpaTryout;
use App\Models\TpaUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ringkasan ketepatan & kecepatan menjawab untuk dasbor latihan SKD.
 * Pengelompokan dilakukan di PHP agar sama di SQLite (lokal) dan MySQL (server).
 */
class StatsService
{
    public const WINDOWS = [3 => '3 hari terakhir', 7 => '1 minggu terakhir', 30 => '1 bulan terakhir'];

    /**
     * @return array{windows: array, chart: array}
     */
    public function forUser(TpaUser $user): array
    {
        $since = now()->subDays(30)->startOfDay();

        $tryouts = TpaTryout::query()
            ->where('tpa_user_id', $user->id)
            ->where('status', TpaTryout::STATUS_FINISHED)
            ->where('finished_at', '>=', $since)
            ->get();

        $windows = [];
        foreach (self::WINDOWS as $days => $label) {
            $from = now()->subDays($days);
            $subset = $tryouts->filter(fn (TpaTryout $t) => $t->finished_at->gte($from));

            $windows[$days] = [
                'label' => $label,
                'all' => $this->summarize($subset),
                'categories' => collect(array_keys(config('tpa.categories')))
                    ->mapWithKeys(fn ($c) => [$c => $this->summarize($subset->where('category', $c))])
                    ->all(),
            ];
        }

        return ['windows' => $windows, 'chart' => $this->dailySeries($tryouts)];
    }

    /**
     * @param  Collection<int, TpaTryout>  $tryouts
     */
    private function summarize(Collection $tryouts): array
    {
        $maxScore = $tryouts->sum('max_score');
        $questions = $tryouts->count() * config('tpa.questions_per_tryout');

        return [
            'tryouts' => $tryouts->count(),
            'accuracy' => $maxScore > 0 ? round($tryouts->sum('score') / $maxScore * 100, 1) : null,
            'avg_seconds' => $questions > 0 ? round($tryouts->sum('total_time_ms') / 1000 / $questions, 1) : null,
            'correct' => $tryouts->sum('correct_count'),
            'questions' => $questions,
        ];
    }

    /**
     * Seri harian 30 hari per kategori: ketepatan (%) dan rata-rata detik per soal.
     * Hari tanpa latihan bernilai null agar garis grafik tidak jatuh ke nol.
     *
     * @param  Collection<int, TpaTryout>  $tryouts
     */
    private function dailySeries(Collection $tryouts): array
    {
        $days = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
        $byDay = $tryouts->groupBy(fn (TpaTryout $t) => $t->finished_at->toDateString());

        $series = [];
        foreach (array_keys(config('tpa.categories')) as $category) {
            $accuracy = [];
            $speed = [];
            foreach ($days as $day) {
                $summary = $this->summarize(($byDay[$day] ?? collect())->where('category', $category));
                $accuracy[] = $summary['accuracy'];
                $speed[] = $summary['avg_seconds'];
            }
            $series[$category] = ['accuracy' => $accuracy, 'speed' => $speed];
        }

        return [
            'labels' => $days->map(fn ($d) => Carbon::parse($d)->translatedFormat('d M'))->all(),
            'series' => $series,
            'names' => collect(config('tpa.categories'))->map(fn ($c) => $c['short'])->all(),
        ];
    }
}
