<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-[10px] font-bold uppercase tracking-wide text-ink-900 dark:text-porcelain-50 sm:text-sm">
                Detail Perhitungan CoCoSo
            </h2>
            <a href="{{ route('assessments.result', $assessment) }}"
               class="rounded-lg border border-black/10 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:bg-brand-50 dark:border-white/10 dark:text-porcelain-200 dark:hover:bg-white/10">
                Kembali ke Hasil
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-6 px-5 sm:px-8 lg:px-10 xl:px-12">

            <x-alert type="info">
                Seluruh angka pada halaman ini dibaca dari data yang tersimpan saat perhitungan dijalankan
                ({{ $assessment->completed_at?->translatedFormat('d F Y, H:i') }}), bukan dihitung ulang.
                Karena itu nilainya tetap sama meskipun bobot kriteria diubah admin setelah tes ini selesai.
            </x-alert>

            {{-- Parameter --}}
            <section class="student-panel p-6 sm:p-7">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Parameter yang Dipakai</h3>
                <dl class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        'Lambda (λ)' => number_format($assessment->lambda_used, 3),
                        'Ambang kelayakan' => number_format($assessment->threshold_used, 2).' ('.$assessment->threshold_mode_used.')',
                        'Jumlah alternatif' => $results->count().' program studi',
                    ] as $label => $value)
                        <div class="student-panel-muted rounded-lg p-4">
                            <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- Bobot --}}
            <section class="student-panel overflow-hidden">
                <div class="p-6 pb-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bobot Kriteria (snapshot)</h3>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="student-table min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Kode</th>
                                <th class="px-6 py-3">Kriteria</th>
                                <th class="px-6 py-3">Tipe</th>
                                <th class="px-6 py-3 text-right">Bobot</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-gray-700 dark:text-gray-300">
                            @foreach ($weights as $code => $meta)
                                <tr>
                                    <td class="px-6 py-3 font-semibold">{{ $code }}</td>
                                    <td class="px-6 py-3">{{ $meta['name'] }}</td>
                                    <td class="px-6 py-3">{{ $meta['type'] }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums">{{ number_format($meta['weight'], 6) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50 font-semibold dark:bg-gray-900/50">
                                <td class="px-6 py-3" colspan="3">Total</td>
                                <td class="px-6 py-3 text-right tabular-nums">
                                    {{ number_format(collect($weights)->sum('weight'), 6) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Tahap 1: matriks keputusan & normalisasi --}}
            @foreach ([
                ['title' => 'Tahap 1a — Matriks Keputusan (x_ij)', 'key' => 'matrix', 'decimals' => 4,
                 'note' => 'Nilai rapor sudah dikalikan bobot relevansi mata pelajaran pada masing-masing program studi.'],
                ['title' => 'Tahap 1b — Matriks Ternormalisasi (r_ij)', 'key' => 'normalized', 'decimals' => 6,
                 'note' => 'r_ij = (x_ij − min_j) / (max_j − min_j) untuk kriteria benefit. Nilai dijaga minimal sebesar epsilon agar P_i tidak nol.'],
            ] as $stage)
                <section class="student-panel overflow-hidden">
                    <div class="p-6 pb-0">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $stage['title'] }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stage['note'] }}</p>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="student-table min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 text-left">Alternatif</th>
                                    @foreach ($codes as $code)
                                        <th class="px-4 py-3 text-right">{{ $code }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-gray-700 dark:text-gray-300">
                                @foreach ($results as $result)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $result->studyProgram->code }}
                                        </td>
                                        @foreach ($codes as $code)
                                            <td class="px-4 py-2.5 text-right tabular-nums">
                                                {{ number_format($result->{$stage['key']}[$code] ?? 0, $stage['decimals']) }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach

            {{-- Tahap 2-5 --}}
            <section class="student-panel overflow-hidden">
                <div class="p-6 pb-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Tahap 2&ndash;5 &mdash; S<sub>i</sub>, P<sub>i</sub>, Strategi Kompromi, dan Nilai Akhir
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        S<sub>i</sub> = Σ(w<sub>j</sub> · r<sub>ij</sub>) &middot;
                        P<sub>i</sub> = Σ(r<sub>ij</sub><sup>w<sub>j</sub></sup>) &middot;
                        K<sub>i</sub> = (K<sub>ia</sub>·K<sub>ib</sub>·K<sub>ic</sub>)<sup>1/3</sup> + ⅓(K<sub>ia</sub>+K<sub>ib</sub>+K<sub>ic</sub>)
                    </p>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="student-table min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Alternatif</th>
                                <th class="px-4 py-3 text-right">S<sub>i</sub></th>
                                <th class="px-4 py-3 text-right">P<sub>i</sub></th>
                                <th class="px-4 py-3 text-right">K<sub>ia</sub></th>
                                <th class="px-4 py-3 text-right">K<sub>ib</sub></th>
                                <th class="px-4 py-3 text-right">K<sub>ic</sub></th>
                                <th class="px-4 py-3 text-right">K<sub>i</sub></th>
                                <th class="px-4 py-3 text-right">Skala 0&ndash;100</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-gray-700 dark:text-gray-300">
                            @foreach ($results as $result)
                                <tr class="{{ $result->study_program_id === $assessment->recommended_program_id ? 'bg-brand-50 dark:bg-brand-900/20' : '' }}">
                                    <td class="px-4 py-2.5 font-semibold">{{ $result->ranking }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $result->studyProgram->code }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($result->s_value, 6) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($result->p_value, 6) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($result->k_a, 6) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($result->k_b, 6) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($result->k_c, 6) }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold tabular-nums">{{ number_format($result->k_value, 6) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($result->k_normal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
