@use('App\Support\Riasec')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Statistik Institusional</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Pola agregat dari {{ $totalCompleted }} tes yang sudah selesai.
                </p>
            </div>

            <form method="GET" class="flex items-end gap-2">
                <div>
                    <x-input-label for="period" value="Gelombang" />
                    <select id="period" name="period" onchange="this.form.submit()"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua gelombang</option>
                        @foreach ($periods as $periodOption)
                            <option value="{{ $periodOption->id }}" @selected($selectedPeriod == $periodOption->id)>
                                {{ $periodOption->name }} &mdash; {{ $periodOption->academic_year }}
                            </option>
                        @endforeach
                        <option value="none" @selected($selectedPeriod === 'none')>Tanpa gelombang</option>
                    </select>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            @if ($totalCompleted === 0)
                <div class="rounded-xl bg-white p-10 text-center shadow-sm dark:bg-gray-800">
                    <p class="text-gray-500 dark:text-gray-400">
                        @if ($selectedPeriod)
                            Belum ada tes yang selesai pada gelombang ini.
                        @else
                            Belum ada tes yang selesai, sehingga statistik belum dapat disusun.
                        @endif
                    </p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Tes Selesai', 'value' => $totalCompleted, 'unit' => 'sesi'],
                        ['label' => 'Rata-rata Kecocokan', 'value' => number_format($averageFit, 1), 'unit' => 'dari 100'],
                        ['label' => 'Sesuai Pilihan Pertama', 'value' => $matchRatio.'%', 'unit' => ''],
                        ['label' => 'Asal Sekolah Terdata', 'value' => $schools->count(), 'unit' => 'sekolah teratas'],
                    ] as $stat)
                        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $stat['value'] }}
                                <span class="text-sm font-normal text-gray-400">{{ $stat['unit'] }}</span>
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Minat vs rekomendasi --}}
                <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Minat dibanding Rekomendasi</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Berapa kali sebuah prodi dijadikan pilihan pertama, dibandingkan berapa kali prodi itu
                            benar-benar direkomendasikan sistem. Selisih positif besar menandakan prodi banyak diminati
                            tetapi jarang cocok &mdash; bahan untuk sosialisasi dan penjurusan.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Program Studi</th>
                                    <th class="px-6 py-3 text-right">Pilihan Pertama</th>
                                    <th class="px-6 py-3 text-right">Direkomendasikan</th>
                                    <th class="px-6 py-3 text-right">Selisih</th>
                                    <th class="px-6 py-3">Bacaan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($interestGap as $row)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $row['program']->full_name }}
                                        </td>
                                        <td class="px-6 py-3 text-right tabular-nums">{{ $row['chosen'] }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums">{{ $row['recommended'] }}</td>
                                        <td class="px-6 py-3 text-right font-semibold tabular-nums {{ $row['gap'] > 0 ? 'text-amber-600 dark:text-amber-400' : ($row['gap'] < 0 ? 'text-sky-600 dark:text-sky-400' : '') }}">
                                            {{ $row['gap'] > 0 ? '+' : '' }}{{ $row['gap'] }}
                                        </td>
                                        <td class="px-6 py-3 text-xs text-gray-500 dark:text-gray-400">
                                            @if ($row['gap'] > 0)
                                                Lebih diminati daripada kecocokannya
                                            @elseif ($row['gap'] < 0)
                                                Lebih sering cocok daripada diminati
                                            @else
                                                Seimbang
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="grid gap-4 lg:grid-cols-2">
                    {{-- Asal sekolah --}}
                    <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Asal Sekolah Terbanyak</h3>
                        </div>

                        @if ($schools->isEmpty())
                            <p class="p-6 text-sm text-gray-500 dark:text-gray-400">Belum ada data asal sekolah.</p>
                        @else
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-3">Sekolah</th>
                                        <th class="px-6 py-3 text-right">Peserta</th>
                                        <th class="px-6 py-3 text-right">Rata-rata Kecocokan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($schools as $school)
                                        <tr class="text-gray-700 dark:text-gray-300">
                                            <td class="px-6 py-3">{{ $school->school_name }}</td>
                                            <td class="px-6 py-3 text-right tabular-nums">{{ $school->total }}</td>
                                            <td class="px-6 py-3 text-right tabular-nums">{{ number_format((float) $school->average_fit, 1) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </section>

                    {{-- Rata-rata nilai rapor --}}
                    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Rata-rata Nilai Rapor Pendaftar</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Berguna untuk menakar tingkat kesiapan akademik pendaftar per mata pelajaran.
                        </p>

                        <div class="mt-5 space-y-3">
                            @foreach ($subjects as $key => $label)
                                @php($value = $subjectAverages[$key] ?? 0)
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                        <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($value, 2) }}</span>
                                    </div>
                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, max(0, $value)) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    {{-- Sebaran tipe RIASEC --}}
                    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Tipe Kepribadian Dominan</h3>
                        <div class="mt-4 space-y-3">
                            @foreach ($dimensionLabels as $code => $label)
                                @php($total = $dominantDistribution[$code] ?? 0)
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                        <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $total }}</span>
                                    </div>
                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-full rounded-full"
                                             style="width: {{ round($total / max($totalCompleted, 1) * 100, 1) }}%; background-color: {{ Riasec::color($code) }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- Jurusan & jenis kelamin --}}
                    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Latar Belakang Pendaftar</h3>

                        <p class="mt-4 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Jurusan Sekolah</p>
                        <ul class="mt-2 space-y-2 text-sm">
                            @forelse ($majors as $label => $total)
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                    <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $total }}</span>
                                </li>
                            @empty
                                <li class="text-gray-500 dark:text-gray-400">Belum ada data.</li>
                            @endforelse
                        </ul>

                        <p class="mt-5 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Jenis Kelamin</p>
                        <ul class="mt-2 space-y-2 text-sm">
                            @forelse ($genders as $label => $total)
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">
                                        {{ $label === 'L' ? 'Laki-laki' : ($label === 'P' ? 'Perempuan' : $label) }}
                                    </span>
                                    <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $total }}</span>
                                </li>
                            @empty
                                <li class="text-gray-500 dark:text-gray-400">Belum ada data.</li>
                            @endforelse
                        </ul>
                    </section>

                    {{-- Tren bulanan --}}
                    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Tren Setahun Terakhir</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jumlah tes selesai per bulan.</p>

                        @if ($monthly->isEmpty())
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Belum ada data.</p>
                        @else
                            @php($peak = max($monthly->all()))
                            <div class="mt-5 flex h-40 items-end gap-1">
                                @foreach ($monthly as $period => $total)
                                    <div class="flex flex-1 flex-col items-center gap-1">
                                        <span class="text-[10px] tabular-nums text-gray-500 dark:text-gray-400">{{ $total }}</span>
                                        <div class="w-full rounded-t bg-brand-500"
                                             style="height: {{ max(4, round($total / max($peak, 1) * 100)) }}%"
                                             title="{{ $period }}: {{ $total }} tes"></div>
                                        <span class="text-[10px] text-gray-400">{{ substr($period, 5) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
