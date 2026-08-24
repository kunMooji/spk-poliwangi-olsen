<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Program Studi</h2></x-slot>

    @php
        $departmentTones = ['#16b6d2', '#20b77a', '#8b63e8', '#e6ae30', '#3286db', '#df5262', '#37aeb8', '#dc4d7e'];
        $programGroups = $programs->getCollection()->groupBy(fn ($program) => $program->department ?: 'Tanpa Jurusan');
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-none px-5 sm:px-8 lg:px-10 xl:px-12" x-data="{ dialog: @js(old('_dialog')) }" x-effect="document.documentElement.style.overflow = dialog ? 'hidden' : ''; document.body.style.overflow = dialog ? 'hidden' : ''">
            <x-flash />

            <section class="relative overflow-hidden rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-5 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20 sm:p-7 lg:p-9">
                <div class="pointer-events-none absolute inset-0 bg-grain opacity-0 dark:opacity-20"></div>

                <div class="relative">
                    <div class="flex flex-wrap items-start justify-between gap-5">
                        <div class="max-w-xl">
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.22em] text-brand-600 dark:text-brand-200">Alternatif keputusan</p>
                            <h1 class="mt-3 text-3xl font-bold tracking-tight text-ink-950 dark:text-white sm:text-4xl">Program Studi</h1>
                            <p class="mt-3 text-sm leading-relaxed text-ink-500 dark:text-porcelain-200/75 sm:text-base">Kelola alternatif program studi dan kelompokkan berdasarkan jurusannya.</p>
                        </div>
                        <button type="button" @click="dialog = 'create'" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-950/30 transition duration-200 hover:-translate-y-0.5 hover:bg-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200/70">
                            <x-heroicon-o-plus class="h-4 w-4" aria-hidden="true" /> Tambah Program Studi
                        </button>
                    </div>

                    <form method="GET" class="mt-7 flex flex-wrap items-end gap-3 rounded-2xl border border-brand-100 bg-white/80 p-4 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-none">
                        <div class="min-w-64 flex-1">
                            <x-input-label for="q" value="Cari program studi" class="text-ink-600 dark:text-porcelain-200/70" />
                            <x-text-input id="q" name="q" type="search" class="mt-1 block w-full border-brand-200 bg-white text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:ring-brand-500 dark:border-white/15 dark:bg-ink-950/50 dark:text-white dark:placeholder:text-porcelain-200/45" :value="request('q')" placeholder="Kode, nama, atau jurusan" />
                        </div>
                        <div class="min-w-36">
                            <x-input-label for="status" value="Status" class="text-ink-600 dark:text-porcelain-200/70" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-brand-200 bg-white text-sm text-ink-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-white/15 dark:bg-ink-950/50 dark:text-white">
                                <option value="">Semua</option>
                                <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                                <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                            </select>
                        </div>
                        <button type="submit" class="rounded-lg bg-ink-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ink-700 dark:bg-white dark:text-ink-950 dark:hover:bg-brand-50">Terapkan</button>
                        @if (request()->hasAny(['q', 'status']))
                            <a href="{{ route('admin.study-programs.index') }}" class="rounded-lg border border-brand-200 px-5 py-2.5 text-sm font-semibold text-ink-600 transition hover:bg-brand-50 dark:border-white/15 dark:text-porcelain-100 dark:hover:bg-white/10">Reset</a>
                        @endif
                    </form>

                    @if ($programGroups->isEmpty())
                        <div class="mt-8 rounded-2xl border border-dashed border-brand-200 bg-white/70 px-6 py-12 text-center text-sm text-ink-500 dark:border-white/15 dark:bg-white/[0.04] dark:text-porcelain-200/70">Tidak ada program studi yang cocok.</div>
                    @else
                        <div class="mt-8 space-y-2.5" x-data="{ openGroup: 0 }">
                            @foreach ($programGroups as $department => $departmentPrograms)
                                @php($tone = $departmentTones[$loop->index % count($departmentTones)])
                                <article class="overflow-hidden rounded-xl border border-brand-100 bg-white shadow-sm shadow-ink-950/5 transition hover:border-brand-300 hover:shadow-lg hover:shadow-ink-950/10 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-lg dark:shadow-black/10 dark:hover:border-white/20 dark:hover:bg-white/[0.08]">
                                    <button type="button" @click="openGroup = openGroup === {{ $loop->index }} ? null : {{ $loop->index }}" :aria-expanded="(openGroup === {{ $loop->index }}).toString()" class="relative flex w-full items-center justify-between gap-4 px-5 py-4 text-left sm:px-6">
                                        <span class="absolute inset-y-0 left-0 w-1" style="background-color: {{ $tone }}"></span>
                                        <span class="flex min-w-0 items-center gap-4">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" style="background-color: {{ $tone }}33; color: {{ $tone }}"><x-heroicon-o-academic-cap class="h-5 w-5" aria-hidden="true" /></span>
                                            <span class="font-mono text-sm font-bold tabular-nums" style="color: {{ $tone }}">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="h-7 w-px bg-ink-200 dark:bg-white/15"></span>
                                            <span class="truncate text-sm font-semibold text-ink-900 dark:text-white sm:text-base">{{ $department }}</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-4">
                                            <span class="hidden rounded-md px-2 py-1 text-[10px] font-bold sm:inline" style="background-color: {{ $tone }}22; color: {{ $tone }}">{{ $departmentPrograms->count() }} prodi</span>
                                            <x-heroicon-o-chevron-down class="h-5 w-5 text-ink-400 transition-transform duration-200 dark:text-porcelain-200/60" x-bind:class="openGroup === {{ $loop->index }} ? 'rotate-180' : ''" aria-hidden="true" />
                                        </span>
                                    </button>

                                    <div x-show="openGroup === {{ $loop->index }}" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" class="border-t border-brand-100 bg-brand-50/50 dark:border-white/10 dark:bg-ink-950/45">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-brand-100 text-sm dark:divide-white/10">
                                                <thead class="bg-brand-50 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-ink-500 dark:bg-black/15 dark:text-porcelain-200/55"><tr><th class="w-10 px-6 py-3">Kode</th><th class="px-6 py-3">Program Studi</th><th class="px-6 py-3">Kode Holland</th><th class="px-6 py-3 text-right">Serapan Kerja</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
                                                <tbody class="divide-y divide-brand-100 text-ink-700 dark:divide-white/10 dark:text-porcelain-100/80">
                                                    @foreach ($departmentPrograms as $program)
                                                        <tr class="transition hover:bg-brand-50/70 dark:hover:bg-white/[0.04]">
                                                            <td class="px-6 py-4 font-mono text-xs text-ink-400 dark:text-porcelain-200/45">{{ $program->code }}</td>
                                                            <td class="px-6 py-4 font-semibold text-ink-950 dark:text-white">{{ $program->full_name }}</td>
                                                            <td class="px-6 py-4 font-mono font-bold">{{ $program->holland_code }}</td>
                                                            <td class="px-6 py-4 text-right font-mono font-bold">{{ number_format($program->employment_percent, 1) }}%</td>
                                                            <td class="px-6 py-4">@if ($program->is_active)<span class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200">Aktif</span>@else<span class="rounded-md bg-ink-100 px-2 py-1 text-xs font-semibold text-ink-500 dark:bg-white/10 dark:text-porcelain-200/65">Nonaktif</span>@endif</td>
                                                            <td class="whitespace-nowrap px-6 py-4 text-right"><div class="inline-flex items-center gap-1"><x-icon-button @click="dialog = 'edit-{{ $program->id }}'" color="brand" title="Ubah"><x-icon.pencil /></x-icon-button><form action="{{ route('admin.study-programs.destroy', $program) }}" method="POST" onsubmit="return confirm('Hapus program studi {{ $program->code }}?')">@csrf @method('DELETE')<x-icon-button type="submit" color="rose" title="Hapus"><x-icon.trash /></x-icon-button></form></div></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-5 border-t border-brand-100 pt-5 dark:border-white/10">{{ $programs->links() }}</div>
                    @endif
                </div>
            </section>

            <div x-show="dialog === 'create'" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-ink-950/55 p-4 backdrop-blur-sm" @keydown.escape.window="dialog = null" role="dialog" aria-modal="true" aria-labelledby="create-program-title">
                <div @click.outside="dialog = null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" class="flex h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-ink-900">
                    <div class="flex shrink-0 items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-7">
                        <div>
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-200">Alternatif keputusan</p>
                            <h2 id="create-program-title" class="mt-1 text-lg font-bold text-ink-950 dark:text-white">Tambah Program Studi</h2>
                        </div>
                        <button type="button" @click="dialog = null" class="rounded-lg p-2 text-ink-400 transition hover:bg-brand-50 hover:text-ink-900 dark:text-porcelain-200/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Tutup dialog">
                            <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-7" data-lenis-prevent>
                        <form method="POST" action="{{ route('admin.study-programs.store') }}">
                            @csrf
                            @include('admin.study-programs.form', ['isModal' => true, 'dialogKey' => 'create'])
                        </form>
                    </div>
                </div>
            </div>

            @foreach ($programs as $editProgram)
                <div x-show="dialog === 'edit-{{ $editProgram->id }}'" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-ink-950/55 p-4 backdrop-blur-sm" @keydown.escape.window="dialog = null" role="dialog" aria-modal="true" aria-labelledby="edit-program-title-{{ $editProgram->id }}">
                    <div @click.outside="dialog = null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" class="flex h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-ink-900">
                        <div class="flex shrink-0 items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-7">
                            <div>
                                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-200">Alternatif keputusan</p>
                                <h2 id="edit-program-title-{{ $editProgram->id }}" class="mt-1 text-lg font-bold text-ink-950 dark:text-white">Ubah Program Studi &mdash; {{ $editProgram->full_name }}</h2>
                            </div>
                            <button type="button" @click="dialog = null" class="rounded-lg p-2 text-ink-400 transition hover:bg-brand-50 hover:text-ink-900 dark:text-porcelain-200/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Tutup dialog">
                                <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-7" data-lenis-prevent>
                            <form method="POST" action="{{ route('admin.study-programs.update', $editProgram) }}">
                                @csrf
                                @method('PUT')
                                @include('admin.study-programs.form', [
                                    'program' => $editProgram,
                                    'selectedSubjects' => $editProgram->supportSubjects->pluck('id')->all(),
                                    'isModal' => true,
                                    'dialogKey' => 'edit-'.$editProgram->id,
                                ])
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
