<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Panel Administrator</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="p-6 sm:p-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Selamat datang,</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</h3>
                    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        Panel ini untuk mengelola data master perhitungan &mdash; program studi, kriteria dan bobot,
                        pernyataan kuesioner, data tracer study, serta parameter algoritma &mdash; sekaligus memantau
                        seluruh tes yang dikerjakan calon mahasiswa. Pengisian tes sendiri dilakukan oleh akun
                        calon mahasiswa, bukan oleh administrator.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('admin.recap.index') }}"
                           class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Lihat Rekap Hasil Tes
                        </a>
                        <a href="{{ route('admin.statistics') }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Statistik Institusional
                        </a>
                        <a href="{{ route('admin.study-programs.index') }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Kelola Program Studi
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Calon Mahasiswa', 'value' => $totalStudents, 'unit' => 'akun'],
                    ['label' => 'Tes Selesai', 'value' => $totalCompleted, 'unit' => 'sesi'],
                    ['label' => 'Tes Berjalan', 'value' => $totalOngoing, 'unit' => 'sesi'],
                    ['label' => 'Total Sesi Tes', 'value' => $totalAssessments, 'unit' => 'sesi'],
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

            @if (abs($totalWeight - 1) > 0.0001)
                <x-alert type="warning">
                    Total bobot kriteria aktif <span class="font-semibold">{{ number_format($totalWeight, 4) }}</span>,
                    belum berjumlah 1. <a href="{{ route('admin.criteria.index') }}" class="font-semibold underline">Perbaiki bobot kriteria</a>.
                </x-alert>
            @endif

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">Data Master</h4>
                    <dl class="mt-4 space-y-3 text-sm">
                        @foreach ([
                            ['Program studi aktif', $programCount, route('admin.study-programs.index')],
                            ['Kriteria aktif', $criteriaCount, route('admin.criteria.index')],
                            ['Pernyataan kuesioner aktif', $questionCount, route('admin.questions.index')],
                        ] as [$label, $value, $url])
                            <div class="flex items-center justify-between">
                                <a href="{{ $url }}" class="text-gray-600 hover:underline dark:text-gray-300">{{ $label }}</a>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $value }}</span>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-300">Total bobot kriteria</span>
                            <span class="font-semibold {{ abs($totalWeight - 1) > 0.0001 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ number_format($totalWeight, 4) }}
                            </span>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">Sebaran Tipe Dominan</h4>
                    @if ($totalCompleted === 0)
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Belum ada tes yang selesai.</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($dimensionLabels as $code => $label)
                                @php($total = $dominantDistribution[$code] ?? 0)
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $total }}</span>
                                    </div>
                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-full rounded-full"
                                             style="width: {{ round($total / max($totalCompleted, 1) * 100, 1) }}%; background-color: {{ \App\Support\Riasec::color($code) }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">Prodi Terpopuler</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Paling sering muncul sebagai rekomendasi utama.</p>

                    @if ($popularPrograms->isEmpty())
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Belum ada data.</p>
                    @else
                        <ol class="mt-4 space-y-3 text-sm">
                            @foreach ($popularPrograms as $row)
                                <li class="flex items-start justify-between gap-3">
                                    <span class="text-gray-600 dark:text-gray-300">{{ $row->recommendedProgram?->full_name ?? '-' }}</span>
                                    <span class="whitespace-nowrap font-semibold text-gray-900 dark:text-gray-100">{{ $row->total }} tes</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($totalCompleted > 0)
                        <div class="mt-5 border-t border-gray-200 pt-4 text-sm dark:border-gray-700">
                            <p class="text-gray-600 dark:text-gray-300">Rekomendasi sesuai pilihan pertama</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ round($matchCount / $totalCompleted * 100, 1) }}%
                                <span class="text-sm font-normal text-gray-400">({{ $matchCount }} dari {{ $totalCompleted }})</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">Tes Terbaru</h4>
                    <a href="{{ route('admin.recap.index') }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                        Lihat semua
                    </a>
                </div>

                @if ($recent->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">Belum ada calon mahasiswa yang mengerjakan tes.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Kode</th>
                                    <th class="px-6 py-3">Calon Mahasiswa</th>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3">Rekomendasi</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($recent as $assessment)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">{{ $assessment->code }}</td>
                                        <td class="px-6 py-4">{{ $assessment->full_name }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $assessment->created_at->translatedFormat('d M Y') }}</td>
                                        <td class="px-6 py-4">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <a href="{{ route('admin.recap.show', $assessment) }}"
                                               class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
