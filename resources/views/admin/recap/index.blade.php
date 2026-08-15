<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Rekap Hasil Tes</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $totalCompleted }} tes selesai dari {{ $totalAll }} sesi yang pernah dibuat calon mahasiswa.
                </p>
            </div>
            <a href="{{ route('admin.recap.export', request()->query()) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                </svg>
                Unduh CSV
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

            <form method="GET" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <x-input-label for="q" value="Cari" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full"
                                  :value="request('q')" placeholder="Nama, kode tes, sekolah, email" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                        <option value="ongoing" @selected(request('status') === 'ongoing')>Belum selesai</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="dominant" value="Tipe Dominan" />
                    <select id="dominant" name="dominant"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        @foreach ($dimensions as $code => $label)
                            <option value="{{ $code }}" @selected(request('dominant') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="program" value="Rekomendasi" />
                    <select id="program" name="program"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua prodi</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(request('program') == $program->id)>{{ $program->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="match" value="Pilihan Pertama" />
                    <select id="match" name="match"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="sesuai" @selected(request('match') === 'sesuai')>Sesuai rekomendasi</option>
                        <option value="beda" @selected(request('match') === 'beda')>Berbeda</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="period" value="Gelombang" />
                    <select id="period" name="period"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua gelombang</option>
                        @foreach ($periods as $periodOption)
                            <option value="{{ $periodOption->id }}" @selected(request('period') == $periodOption->id)>
                                {{ $periodOption->name }} &mdash; {{ $periodOption->academic_year }}
                            </option>
                        @endforeach
                        <option value="none" @selected(request('period') === 'none')>Tanpa gelombang</option>
                    </select>
                </div>

                <div class="flex items-end gap-3 sm:col-span-2 lg:col-span-6">
                    <button type="submit"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                        Terapkan Filter
                    </button>
                    @if (request()->hasAny(['q', 'status', 'dominant', 'program', 'match', 'period']))
                        <a href="{{ route('admin.recap.index') }}"
                           class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            @if (! $assessments->isEmpty())
                <div class="flex justify-end">
                    <x-list-view-toggle />
                </div>
            @endif

            <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                @if ($assessments->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">Belum ada data tes yang cocok dengan filter.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Kode</th>
                                    <th class="px-6 py-3">Calon Mahasiswa</th>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3">Gelombang</th>
                                    <th class="px-6 py-3">Holland</th>
                                    <th class="px-6 py-3">Rekomendasi</th>
                                    <th class="px-6 py-3">Pilihan Pertama</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($assessments as $assessment)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">{{ $assessment->code }}</td>
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $assessment->full_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $assessment->user?->email ?? '-' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $assessment->created_at->translatedFormat('d M Y') }}</td>
                                        <td class="px-6 py-4">
                                            @if ($assessment->period)
                                                <span class="text-gray-700 dark:text-gray-300">{{ $assessment->period->name }}</span>
                                                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $assessment->period->academic_year }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-semibold">{{ $assessment->holland_code ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            @if (! $assessment->isCompleted())
                                                <span class="text-gray-400">-</span>
                                            @elseif ($assessment->matches_preference)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Sesuai</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Berbeda</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($assessment->isCompleted())
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>
                                            @else
                                                <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Belum selesai</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-1">
                                                <x-icon-button :href="route('admin.recap.show', $assessment)" color="brand" title="Detail">
                                                    <x-icon.eye />
                                                </x-icon-button>

                                                <form action="{{ route('admin.recap.destroy', $assessment) }}" method="POST"
                                                      onsubmit="return confirm('Hapus data tes {{ $assessment->code }} milik {{ $assessment->full_name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-icon-button type="submit" color="rose" title="Hapus">
                                                        <x-icon.trash />
                                                    </x-icon-button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        {{ $assessments->links() }}
                    </div>
                @endif
            </div>

            @if (! $assessments->isEmpty())
                <div x-show="view === 'card'" x-cloak>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($assessments as $assessment)
                            @php($cardColor = $assessment->dominant_type ? \App\Support\Riasec::color($assessment->dominant_type) : '#6b7280')
                            <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                                <div class="relative overflow-hidden p-5 text-white"
                                     style="background-color: {{ $cardColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                    <span class="pointer-events-none absolute -right-3 -top-8 select-none text-8xl font-black leading-none text-white/10">
                                        {{ $assessment->dominant_type ?? '?' }}
                                    </span>

                                    <div class="relative flex items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-start gap-2.5">
                                            <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                                </svg>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-base font-semibold">{{ $assessment->full_name }}</p>
                                                <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-white/80">
                                                    <svg class="h-3.5 w-3.5 flex-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                                    </svg>
                                                    {{ $assessment->user?->email ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="inline-flex flex-none items-center gap-1 whitespace-nowrap rounded-full bg-white/20 px-2 py-1 font-mono text-[11px] font-medium backdrop-blur-sm">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.83.699 2.528 0l4.319-4.319a1.789 1.789 0 000-2.528l-9.581-9.581A2.25 2.25 0 009.568 3z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                            </svg>
                                            {{ $assessment->code }}
                                        </span>
                                    </div>
                                </div>

                                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 p-5 pb-3 text-sm">
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Tanggal</dt>
                                        <dd class="text-gray-700 dark:text-gray-300">{{ $assessment->created_at->translatedFormat('d M Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Gelombang</dt>
                                        <dd class="text-gray-700 dark:text-gray-300">{{ $assessment->period?->name ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Holland</dt>
                                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $assessment->holland_code ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Pilihan Pertama</dt>
                                        <dd>
                                            @if (! $assessment->isCompleted())
                                                <span class="text-gray-400">-</span>
                                            @elseif ($assessment->matches_preference)
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Sesuai</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Berbeda</span>
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Rekomendasi</dt>
                                        <dd class="text-gray-700 dark:text-gray-300">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</dd>
                                    </div>
                                </dl>

                                <div class="flex items-center justify-between border-t border-gray-100 px-5 pb-5 pt-3 dark:border-gray-700">
                                    @if ($assessment->isCompleted())
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>
                                    @else
                                        <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Belum selesai</span>
                                    @endif

                                    <div class="inline-flex items-center gap-1">
                                        <x-icon-button :href="route('admin.recap.show', $assessment)" color="brand" title="Detail">
                                            <x-icon.eye />
                                        </x-icon-button>

                                        <form action="{{ route('admin.recap.destroy', $assessment) }}" method="POST"
                                              onsubmit="return confirm('Hapus data tes {{ $assessment->code }} milik {{ $assessment->full_name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-icon-button type="submit" color="rose" title="Hapus">
                                                <x-icon.trash />
                                            </x-icon-button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $assessments->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
