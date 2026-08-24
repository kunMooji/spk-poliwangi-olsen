<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Analisis Sensitivitas &mdash; {{ $assessment->full_name }}</h2></x-slot>

    @php
        $winner = $analysis['baseline']['winner'];
        $summary = $analysis['summary'];
        $name = fn ($id) => $programs[$id]?->full_name ?? '-';
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            <div class="flex justify-end"><a href="{{ route('admin.recap.show', $assessment) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Kembali ke Detail</a></div>

            <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ringkasan</h3>
                <p class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                    Peringkat 1 hasil perhitungan asli adalah <strong class="text-gray-900 dark:text-gray-100">{{ $name($winner) }}</strong>.
                    Seluruh skenario di bawah menghitung ulang CoCoSo memakai matriks keputusan yang tersimpan pada
                    sesi tes ini, dengan λ dan bobot yang digeser. Hasil asli tidak diubah sama sekali.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Skenario Stabil</p>
                        <p class="mt-1 text-3xl font-bold {{ $summary['ratio'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $summary['ratio'] }}%
                            <span class="text-sm font-normal text-gray-400">{{ $summary['stable'] }}/{{ $summary['total'] }}</span>
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ketahanan terhadap λ</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $summary['lambda_stable'] ? 'Tidak terpengaruh' : 'Terpengaruh' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $summary['lambda_stable']
                                ? 'Peringkat 1 bertahan pada seluruh nilai λ dari 0 sampai 1.'
                                : 'Peringkat 1 berpindah pada sebagian nilai λ.' }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kriteria Penentu</p>
                        @if ($summary['critical'] === [])
                            <p class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">Tidak ada</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Tidak ada kriteria yang pergeseran bobotnya memindahkan peringkat 1.
                            </p>
                        @else
                            <p class="mt-1 text-lg font-semibold text-amber-600 dark:text-amber-400">
                                {{ implode(', ', $summary['critical']) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Menggeser bobot kriteria ini memindahkan peringkat 1.
                            </p>
                        @endif
                    </div>
                </div>

                <x-alert :type="$summary['ratio'] >= 80 ? 'success' : 'warning'" class="mt-5">
                    @if ($summary['ratio'] >= 80)
                        Rekomendasi tergolong <strong>kokoh</strong>: peringkat 1 bertahan di {{ $summary['stable'] }}
                        dari {{ $summary['total'] }} skenario, sehingga hasilnya tidak bergantung pada satu pilihan
                        parameter tertentu.
                    @else
                        Rekomendasi tergolong <strong>sensitif</strong>: peringkat 1 hanya bertahan di
                        {{ $summary['stable'] }} dari {{ $summary['total'] }} skenario. Selisih nilai antar prodi
                        teratas tipis, jadi penetapan bobot perlu dasar yang kuat.
                    @endif
                </x-alert>
            </section>

            <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pengaruh Nilai λ</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        λ mengatur keseimbangan antara S<sub>i</sub> (weighted sum) dan P<sub>i</sub> (weighted product)
                        pada strategi K<sub>ic</sub>. Perhitungan asli memakai λ = {{ number_format($assessment->lambda_used, 2) }}.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">λ</th>
                                <th class="px-6 py-3">Peringkat 1</th>
                                <th class="px-6 py-3 text-right">Selisih ke Peringkat 2</th>
                                <th class="px-6 py-3">Hasil</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($analysis['lambda'] as $row)
                                <tr class="text-gray-700 dark:text-gray-300 {{ abs($row['lambda'] - $assessment->lambda_used) < 0.001 ? 'bg-brand-50 dark:bg-brand-900/20' : '' }}">
                                    <td class="whitespace-nowrap px-6 py-3 font-mono">{{ number_format($row['lambda'], 1) }}</td>
                                    <td class="px-6 py-3 text-gray-900 dark:text-gray-100">{{ $name($row['winner']) }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right font-mono">{{ number_format($row['margin'], 2) }}</td>
                                    <td class="px-6 py-3">
                                        @if ($row['stable'])
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Tetap</span>
                                        @else
                                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Berpindah</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pengaruh Pergeseran Bobot</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Bobot satu kriteria digeser, bobot kriteria lain diskalakan ulang secara proporsional agar
                        totalnya tetap sama. Kolom terakhir menunjukkan peringkat yang ditempati
                        <strong>{{ $name($winner) }}</strong> pada skenario tersebut.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Kriteria</th>
                                <th class="px-6 py-3">Pergeseran</th>
                                <th class="px-6 py-3 text-right">Bobot Baru</th>
                                <th class="px-6 py-3">Peringkat 1 Menjadi</th>
                                <th class="px-6 py-3 text-right">Peringkat {{ $name($winner) }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($analysis['weights'] as $row)
                                <tr class="text-gray-700 dark:text-gray-300 {{ $row['stable'] ? '' : 'bg-rose-50 dark:bg-rose-900/20' }}">
                                    <td class="px-6 py-3">
                                        <span class="font-mono text-xs">{{ $row['code'] }}</span>
                                        <span class="ms-2 text-gray-500 dark:text-gray-400">{{ $snapshot[$row['code']]['name'] ?? '' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3">
                                        {{ $row['shift'] > 0 ? '+' : '' }}{{ round($row['shift'] * 100) }}%
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right font-mono">{{ number_format($row['weight'], 4) }}</td>
                                    <td class="px-6 py-3 {{ $row['stable'] ? '' : 'font-semibold text-rose-700 dark:text-rose-300' }}">
                                        {{ $name($row['winner']) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right font-semibold">{{ $row['rank_of_baseline_winner'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
