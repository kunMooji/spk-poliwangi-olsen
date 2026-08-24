<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Detail Tes &mdash; {{ $assessment->full_name }}</h2></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            <div class="flex justify-end"><a href="{{ route('admin.recap.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Kembali ke Rekap</a></div>

            @unless ($assessment->isCompleted())
                <x-alert type="warning">
                    Sesi tes ini belum diselesaikan calon mahasiswa, sehingga hasil perhitungan belum tersedia.
                </x-alert>
            @endunless

            <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Biodata</h3>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                    @foreach ([
                        'Akun' => $assessment->user?->email ?? '-',
                        'Jenis Kelamin' => match ($assessment->gender) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-' },
                        'Nomor HP' => $assessment->phone ?? '-',
                        'Asal Sekolah' => $assessment->school_name ?? '-',
                        'Jenjang' => $assessment->education_level ?? '-',
                        ($assessment->education_level === 'SMK' ? 'Rumpun Keahlian' : 'Jurusan') => $assessment->school_major ?? '-',
                        'Tahun Lulus' => $assessment->graduation_year ?? '-',
                        'Dibuat' => $assessment->created_at->translatedFormat('d F Y, H:i'),
                        'Diselesaikan' => $assessment->completed_at?->translatedFormat('d F Y, H:i') ?? '-',
                    ] as $label => $value)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nilai Rapor</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Rerata seluruh mapel
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($assessment->rapor_average, 2) }}</span>
                    </p>
                </div>

                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-5">
                    @foreach ($assessment->raporSemesters as $semester)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Semester {{ $semester->semester }}</dt>
                            <dd class="mt-0.5 text-xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($semester->average_score, 2) }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @php($completedSubjectScores = $assessment->subjectScores->filter(fn ($row) => $row->score !== null))
                @if ($completedSubjectScores->isNotEmpty())
                    <h4 class="mt-6 text-sm font-semibold text-gray-900 dark:text-gray-100">Mata Pelajaran Pendukung</h4>
                    <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-4">
                        @foreach ($completedSubjectScores as $row)
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ $row->subject?->name ?? '—' }}</dt>
                                <dd class="mt-0.5 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($row->score, 2) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Profil RIASEC</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Kode Holland <span class="font-semibold">{{ $assessment->holland_code ?? '-' }}</span>
                    @if ($assessment->dominant_type)
                        &middot; tipe dominan {{ \App\Support\Riasec::label($assessment->dominant_type) }}
                    @endif
                </p>

                <div class="mt-5 space-y-3">
                    @foreach ($percentages as $dimension => $percent)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ \App\Support\Riasec::label($dimension) }}</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($percent, 1) }}%</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full"
                                     style="width: {{ min(100, max(0, $percent)) }}%; background-color: {{ \App\Support\Riasec::color($dimension) }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Prioritas Pilihan Calon Mahasiswa</h3>
                <ol class="mt-4 space-y-2 text-sm">
                    @forelse ($assessment->priorities as $priority)
                        <li class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $priority->priority_order }}
                            </span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $priority->studyProgram?->full_name ?? '-' }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500 dark:text-gray-400">Tidak ada data prioritas.</li>
                    @endforelse
                </ol>
            </section>

            @if ($assessment->isCompleted())
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Hasil Rekomendasi</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Threshold {{ number_format($assessment->threshold_used, 2) }}
                                (mode {{ $assessment->threshold_mode_used }})
                                &middot; &lambda; = {{ number_format($assessment->lambda_used, 2) }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('admin.recap.sensitivity', $assessment) }}"
                               class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                                Analisis Sensitivitas
                            </a>
                            <a href="{{ route('assessments.result', $assessment) }}"
                               class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                                Lembar Hasil
                            </a>
                            <a href="{{ route('assessments.calculation', $assessment) }}"
                               class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                Rincian Perhitungan
                            </a>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Peringkat</th>
                                    <th class="px-4 py-3">Program Studi</th>
                                    <th class="px-4 py-3 text-right">S<sub>i</sub></th>
                                    <th class="px-4 py-3 text-right">P<sub>i</sub></th>
                                    <th class="px-4 py-3 text-right">K<sub>i</sub></th>
                                    <th class="px-4 py-3 text-right">K ternormalisasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($topResults as $result)
                                    <tr class="text-gray-700 dark:text-gray-300 {{ $result->study_program_id === $assessment->recommended_program_id ? 'bg-brand-50 dark:bg-brand-900/20' : '' }}">
                                        <td class="px-4 py-3 font-semibold">{{ $result->ranking }}</td>
                                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $result->studyProgram?->full_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($result->s_value, 4) }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($result->p_value, 4) }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($result->k_value, 4) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($result->k_normal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
