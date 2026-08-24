<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-[10px] font-bold uppercase tracking-wide text-ink-900 dark:text-porcelain-50 sm:text-sm">Pusat kendali administrator</h2>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto flex max-w-none flex-col gap-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            <div class="dashboard-enter">
                <section class="relative overflow-hidden rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-5 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20 sm:p-7 lg:p-9">
                    <div class="pointer-events-none absolute inset-0 bg-grain opacity-0 dark:opacity-20"></div>
                    <div class="relative">
                        <span class="pointer-events-none absolute -left-3 top-3 hidden grid grid-cols-3 gap-2 opacity-25 lg:grid"><i class="h-1 w-1 rounded-full bg-brand-600"></i><i class="h-1 w-1 rounded-full bg-brand-600"></i><i class="h-1 w-1 rounded-full bg-brand-600"></i><i class="h-1 w-1 rounded-full bg-brand-600"></i><i class="h-1 w-1 rounded-full bg-brand-600"></i><i class="h-1 w-1 rounded-full bg-brand-600"></i></span>
                        <p class="ml-0 flex items-center gap-2 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-200 lg:ml-6"><x-heroicon-o-calendar-days class="h-4 w-4" />{{ now()->translatedFormat('l, d F Y') }}</p>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-950 dark:text-white sm:text-4xl">Pusat kendali administrator</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-600 dark:text-porcelain-200/75 sm:text-base">Pantau sesi calon mahasiswa, kelola data perhitungan, dan tinjau kesehatan parameter rekomendasi dalam satu tempat.</p>

                        <div class="mt-8 grid gap-6 lg:grid-cols-2 lg:items-center">
                            <div class="space-y-5 lg:order-2">
                                <section class="rounded-2xl border border-brand-100 bg-white/90 p-5 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-none">
                                    <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-brand-700 dark:text-brand-200"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-200"><x-heroicon-o-chart-bar class="h-4 w-4" /></span>Ringkasan perhitungan</p>
                                    <div class="mt-5 flex items-end justify-between gap-3">
                                        <div><p class="text-xs text-ink-500 dark:text-porcelain-200/70">Total bobot saat ini</p><p class="mt-2 text-2xl font-bold tabular-nums text-ink-950 dark:text-white">{{ number_format($totalWeight, 4) }}</p></div>
                                        <span @class([
                                            'rounded-lg px-3 py-1.5 text-xs font-bold',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200' => abs($totalWeight - 1) <= 0.0001,
                                            'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-100' => abs($totalWeight - 1) > 0.0001,
                                        ])>{{ abs($totalWeight - 1) <= 0.0001 ? 'Valid' : 'Perlu dicek' }}</span>
                                    </div>
                                    <div class="mt-5 grid grid-cols-2 gap-3 border-t border-brand-100 pt-4 dark:border-white/10"><div class="flex items-center gap-3 rounded-xl border border-brand-100 bg-brand-50/50 px-3 py-2.5 dark:border-white/10 dark:bg-brand-500/10"><x-heroicon-o-adjustments-horizontal class="h-5 w-5 text-brand-600 dark:text-brand-200" /><span><b class="block text-base leading-none text-ink-900 dark:text-white">{{ $criteriaCount }}</b><small class="text-[10px] text-ink-500 dark:text-porcelain-200/65">kriteria aktif</small></span></div><div class="flex items-center gap-3 rounded-xl border border-brand-100 bg-brand-50/50 px-3 py-2.5 dark:border-white/10 dark:bg-brand-500/10"><x-heroicon-o-document-text class="h-5 w-5 text-brand-600 dark:text-brand-200" /><span><b class="block text-base leading-none text-ink-900 dark:text-white">{{ $questionCount }}</b><small class="text-[10px] text-ink-500 dark:text-porcelain-200/65">pernyataan RIASEC</small></span></div></div>
                                </section>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('admin.recap.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-950/25 transition hover:-translate-y-0.5 hover:bg-brand-500 active:scale-[0.98]">Lihat rekap hasil <x-heroicon-o-chevron-right class="h-4 w-4" /></a>
                                    <a href="{{ route('admin.study-programs.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-white/80 px-5 py-3 text-sm font-semibold text-ink-800 transition hover:bg-brand-50 dark:border-white/15 dark:bg-white/[0.06] dark:text-white dark:hover:bg-white/10">Kelola program studi <x-heroicon-o-cog-6-tooth class="h-4 w-4" /></a>
                                </div>
                            </div>

                            <div class="space-y-4 lg:order-1"><aside class="rounded-2xl border border-brand-100 bg-white/80 p-5 shadow-lg shadow-ink-950/10 dark:border-white/10 dark:bg-ink-900/75">
                                <p class="text-xs font-bold uppercase tracking-wide text-brand-700 dark:text-brand-200">Profil administrator</p>
                                <div class="mt-5 flex items-center gap-4">
                                    <div class="relative flex h-24 w-24 shrink-0 items-center justify-center rounded-full border-4 border-white bg-brand-100 text-brand-600 shadow-md shadow-brand-950/10 dark:border-ink-800 dark:bg-brand-500/15 dark:text-brand-200">
                                        @if (auth()->user()->avatar)
                                            <img src="{{ auth()->user()->avatar_url }}" alt="Foto profil {{ auth()->user()->name }}" class="h-full w-full rounded-full object-cover">
                                        @else
                                            <x-heroicon-o-user class="h-12 w-12" />
                                        @endif
                                        <span class="absolute bottom-0 right-0 h-5 w-5 rounded-full border-2 border-white bg-emerald-500 dark:border-ink-800"></span>
                                    </div>
                                    <div class="min-w-0"><p class="truncate text-xl font-bold text-ink-950 dark:text-white">{{ auth()->user()->name }}</p><p class="mt-1 text-sm font-medium text-brand-700 dark:text-brand-200">Administrator</p></div>
                                </div>
                                <div class="mt-5 space-y-3 text-sm text-ink-600 dark:text-porcelain-200/75">
                                    <p class="flex items-center gap-3"><x-heroicon-o-envelope class="h-5 w-5 shrink-0 text-brand-600 dark:text-brand-300" /><span class="truncate">{{ auth()->user()->email }}</span></p>
                                    <p class="flex items-center gap-3"><x-heroicon-o-building-library class="h-5 w-5 shrink-0 text-brand-600 dark:text-brand-300" />Politeknik Negeri Banyuwangi</p>
                                    <p class="flex items-center gap-3"><x-heroicon-o-calendar-days class="h-5 w-5 shrink-0 text-brand-600 dark:text-brand-300" />Bergabung sejak {{ auth()->user()->created_at->translatedFormat('d M Y') }}</p>
                                </div>
                            </aside><div class="grid grid-cols-2 divide-x divide-brand-100 rounded-2xl border border-brand-100 bg-white/80 py-3 shadow-sm shadow-ink-950/5 dark:divide-white/10 dark:border-white/10 dark:bg-ink-900/75">
                                    <div class="flex items-center gap-2 px-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-heroicon-o-user-group class="h-5 w-5" /></span><span><b class="block text-lg leading-none text-brand-700 dark:text-brand-200">{{ $totalStudents }}</b><small class="text-[10px] text-ink-500 dark:text-porcelain-200/65">Pengguna terdaftar</small></span></div>
                                    <div class="flex items-center gap-2 px-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-heroicon-o-academic-cap class="h-5 w-5" /></span><span><b class="block text-lg leading-none text-brand-700 dark:text-brand-200">{{ $programCount }}</b><small class="text-[10px] text-ink-500 dark:text-porcelain-200/65">Program studi aktif</small></span></div>
                            </div></div>
                        </div>
                    </div>
                </section>
            </div>

            <section class="dashboard-enter dashboard-enter-2 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Calon mahasiswa', 'value' => $totalStudents, 'unit' => 'akun', 'icon' => 'user-group', 'description' => 'Total akun calon mahasiswa terdaftar', 'trend' => $trends['students'], 'headerStyle' => 'background: linear-gradient(135deg, #42A5F5, #1976D2)', 'iconSurface' => 'bg-blue-50 text-blue-600 dark:bg-blue-400/15 dark:text-blue-200'],
                    ['label' => 'Tes selesai', 'value' => $totalCompleted, 'unit' => 'sesi', 'icon' => 'clipboard-document-check', 'description' => 'Sesi tes yang telah diselesaikan', 'trend' => $trends['completed'], 'headerStyle' => 'background: linear-gradient(135deg, #26C281, #1AA368)', 'iconSurface' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-200'],
                    ['label' => 'Tes berjalan', 'value' => $totalOngoing, 'unit' => 'sesi', 'icon' => 'clock', 'description' => 'Sesi tes yang sedang berlangsung', 'trend' => $trends['ongoing'], 'headerStyle' => 'background: linear-gradient(135deg, #FFA200, #FDC15A)', 'iconSurface' => 'bg-amber-50 text-amber-600 dark:bg-amber-400/15 dark:text-amber-200'],
                    ['label' => 'Total sesi tes', 'value' => $totalAssessments, 'unit' => 'sesi', 'icon' => 'chart-bar', 'description' => 'Total seluruh sesi tes', 'trend' => $trends['assessments'], 'headerStyle' => 'background: linear-gradient(135deg, #AB47BC, #7B1FA2)', 'iconSurface' => 'bg-violet-50 text-violet-600 dark:bg-violet-400/15 dark:text-violet-200'],
                ] as $stat)
                    <article class="dashboard-lift overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm shadow-ink-950/5 transition duration-300 ease-brand-out hover:-translate-y-0.5 hover:shadow-lg hover:shadow-ink-950/10 dark:border-white/10 dark:bg-ink-900/60">
                        <div class="px-5 py-4 text-white" style="{{ $stat['headerStyle'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-bold">{{ $stat['label'] }}</p>
                                    <p class="mt-1 text-xs font-medium text-brand-100">Perbandingan minggu ini</p>
                                </div>
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white">
                                    <x-dynamic-component :component="'heroicon-o-'.$stat['icon']" class="h-5 w-5" />
                                </span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="rounded-lg bg-white/15 px-2.5 py-1.5">{{ $stat['value'] }} {{ $stat['unit'] }}</span>
                                <span class="inline-flex items-center gap-1 rounded-lg bg-white/15 px-2.5 py-1.5">
                                    <x-dynamic-component :component="'heroicon-o-'.($stat['trend']['direction'] === 'up' ? 'arrow-up-right' : ($stat['trend']['direction'] === 'down' ? 'arrow-down-right' : 'minus'))" class="h-3.5 w-3.5" />
                                    {{ $stat['trend']['label'] }}
                                </span>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $stat['iconSurface'] }}">
                                    <x-dynamic-component :component="'heroicon-o-'.$stat['icon']" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs text-ink-500 dark:text-porcelain-300/70">Jumlah keseluruhan</p>
                                    <p class="mt-0.5 text-2xl font-bold leading-none tracking-tight tabular-nums text-ink-950 dark:text-porcelain-50">{{ $stat['value'] }} <span class="text-xs font-medium text-ink-500 dark:text-porcelain-300/70">{{ $stat['unit'] }}</span></p>
                                </div>
                            </div>
                            <div class="mt-4 border-t border-black/5 pt-3 dark:border-white/10">
                                <p class="text-xs font-medium leading-relaxed text-ink-600 dark:text-porcelain-200/75">{{ $stat['description'] }}</p>
                                <p class="mt-1 text-xs text-ink-400 dark:text-porcelain-300/55">{{ $stat['trend']['period'] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            @if (abs($totalWeight - 1) > 0.0001)
                <x-alert type="warning">Total bobot kriteria aktif <span class="font-semibold">{{ number_format($totalWeight, 4) }}</span>, belum berjumlah 1. <a href="{{ route('admin.criteria.index') }}" class="font-semibold underline">Perbaiki bobot kriteria</a>.</x-alert>
            @endif

            @php
                $dimensionStyles = [
                    'R' => ['icon' => 'briefcase', 'color' => '#3598f8', 'surface' => 'bg-blue-50 text-blue-500 dark:bg-blue-400/15 dark:text-blue-300'],
                    'I' => ['icon' => 'magnifying-glass', 'color' => '#14b8a6', 'surface' => 'bg-teal-50 text-teal-500 dark:bg-teal-400/15 dark:text-teal-300'],
                    'A' => ['icon' => 'paint-brush', 'color' => '#8b5cf6', 'surface' => 'bg-violet-50 text-violet-500 dark:bg-violet-400/15 dark:text-violet-300'],
                    'S' => ['icon' => 'user-group', 'color' => '#f59e0b', 'surface' => 'bg-amber-50 text-amber-500 dark:bg-amber-400/15 dark:text-amber-300'],
                    'E' => ['icon' => 'arrow-trending-up', 'color' => '#f16b4a', 'surface' => 'bg-rose-50 text-rose-500 dark:bg-rose-400/15 dark:text-rose-300'],
                    'C' => ['icon' => 'clipboard-document-list', 'color' => '#3297e8', 'surface' => 'bg-sky-50 text-sky-500 dark:bg-sky-400/15 dark:text-sky-300'],
                ];
                $programGradients = [
                    'linear-gradient(135deg, #66c8ff 0%, #3188f5 52%, #195cdb 100%)',
                    'linear-gradient(135deg, #b579ff 0%, #7c3aed 52%, #5b21b6 100%)',
                    'linear-gradient(135deg, #ffd45a 0%, #fb9a2f 52%, #f26a18 100%)',
                    'linear-gradient(135deg, #54dfb7 0%, #14b8a6 52%, #0f7490 100%)',
                    'linear-gradient(135deg, #fb8baa 0%, #ec4899 52%, #a21caf 100%)',
                ];
            @endphp

            <section class="dashboard-enter dashboard-enter-3 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(18rem,.85fr)]">
                <article class="dashboard-lift rounded-2xl border border-black/5 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-ink-900/60 sm:p-7">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Profil hasil</p>
                            <h2 class="mt-2 text-xl font-bold tracking-tight text-ink-950 dark:text-porcelain-50">Sebaran tipe dominan</h2>
                            <p class="mt-1 max-w-md text-sm leading-relaxed text-ink-500 dark:text-porcelain-300/70">Komposisi Holland Code dari seluruh tes yang telah selesai.</p>
                        </div>
                        <a href="{{ route('admin.statistics') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-600 transition hover:text-brand-500 dark:text-brand-300">Statistik lengkap <x-heroicon-o-chevron-right class="h-4 w-4" /></a>
                    </div>
                    @if ($totalCompleted === 0)
                        <div class="mt-6 flex min-h-64 flex-col items-center justify-center rounded-2xl border border-dashed border-black/10 bg-porcelain-50 px-6 text-center dark:border-white/10 dark:bg-ink-950/40"><x-heroicon-o-chart-pie class="h-7 w-7 text-brand-600 dark:text-brand-300" /><p class="mt-4 font-semibold text-ink-800 dark:text-porcelain-100">Belum ada tes yang selesai</p><p class="mt-1 text-sm text-ink-500 dark:text-porcelain-300/70">Sebaran RIASEC akan tersedia setelah sesi pertama diselesaikan.</p></div>
                    @else
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            @foreach ($dimensionLabels as $code => $label)
                                @php($total = $dominantDistribution[$code] ?? 0)
                                @php($style = $dimensionStyles[$code])
                                <div class="rounded-2xl border border-black/5 bg-white p-3.5 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.04]">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="flex min-w-0 items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $style['surface'] }}"><x-dynamic-component :component="'heroicon-o-'.$style['icon']" class="h-5 w-5" /></span>
                                            <span class="truncate text-sm font-semibold text-ink-800 dark:text-porcelain-100">{{ $label }}</span>
                                        </span>
                                        <span class="flex h-8 min-w-8 items-center justify-center rounded-full border px-2 font-mono text-sm font-bold tabular-nums text-ink-800 dark:border-white/15 dark:text-porcelain-100" style="border-color: {{ $style['color'] }}66">{{ $total }}</span>
                                    </div>
                                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-porcelain-100 shadow-inner shadow-ink-950/5 dark:bg-ink-950">
                                        <div class="h-full rounded-full" style="width: {{ round($total / max($totalCompleted, 1) * 100, 1) }}%; background-color: {{ $style['color'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5 flex gap-3 rounded-xl bg-brand-50/70 px-4 py-3 text-xs leading-relaxed text-ink-600 dark:bg-brand-500/10 dark:text-porcelain-200/75"><x-heroicon-o-sparkles class="h-5 w-5 shrink-0 text-brand-600 dark:text-brand-300" /><p>Skor menunjukkan kecenderungan tipe kepribadian kerja menurut Holland Code. Semakin tinggi skor, semakin dominan tipe tersebut.</p></div>
                    @endif
                </article>

                <article class="dashboard-lift rounded-2xl border border-black/5 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-ink-900/60 sm:p-7">
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Rekomendasi sistem</p><h2 class="mt-2 text-xl font-bold tracking-tight text-ink-950 dark:text-porcelain-50">Program studi terpopuler</h2><p class="mt-1 text-sm leading-relaxed text-ink-500 dark:text-porcelain-300/70">Program studi yang paling sering menjadi rekomendasi utama.</p>
                    @if ($popularPrograms->isEmpty())
                        <div class="mt-6 rounded-2xl bg-porcelain-50 p-5 dark:bg-ink-950/40"><p class="text-sm font-semibold text-ink-800 dark:text-porcelain-100">Belum ada data rekomendasi</p><p class="mt-1 text-sm text-ink-500 dark:text-porcelain-300/70">Data akan terisi saat ada tes yang selesai.</p></div>
                    @else
                        <ol class="mt-6 space-y-4">
                            @foreach ($popularPrograms as $index => $row)
                                <li class="relative overflow-hidden rounded-2xl p-4 text-white shadow-lg shadow-ink-950/10" style="background: {{ $programGradients[$index % count($programGradients)] }}">
                                    <span class="pointer-events-none absolute -right-5 -top-6 h-24 w-24 rounded-full border border-white/10"></span>
                                    <span class="pointer-events-none absolute right-5 top-3 grid grid-cols-3 gap-1 opacity-35"><i class="h-1 w-1 rounded-full bg-white"></i><i class="h-1 w-1 rounded-full bg-white"></i><i class="h-1 w-1 rounded-full bg-white"></i><i class="h-1 w-1 rounded-full bg-white"></i><i class="h-1 w-1 rounded-full bg-white"></i><i class="h-1 w-1 rounded-full bg-white"></i></span>
                                    <div class="relative flex items-center gap-3">
                                        <span class="shrink-0 font-mono text-sm font-bold tabular-nums text-white">{{ $index + 1 }}</span>
                                        <span class="min-w-0 flex-1 text-sm font-bold leading-snug sm:text-base">{{ $row->recommendedProgram?->full_name ?? '-' }}</span>
                                        <span class="shrink-0 rounded-lg bg-white/15 px-3 py-2 text-xs font-bold">{{ $row->total }} tes</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                    @if ($totalCompleted > 0)
                        <div class="mt-5 border-t border-black/5 pt-5 dark:border-white/10"><p class="text-sm text-ink-500 dark:text-porcelain-300/70">Sesuai pilihan pertama siswa</p><p class="mt-1 text-2xl font-semibold tabular-nums text-ink-950 dark:text-porcelain-50">{{ round($matchCount / $totalCompleted * 100, 1) }}% <span class="text-xs font-normal text-ink-400">({{ $matchCount }}/{{ $totalCompleted }})</span></p></div>
                    @endif
                </article>
            </section>

            <section class="dashboard-enter dashboard-enter-3 overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-ink-900/60">
                <div class="flex items-center justify-between gap-4 border-b border-black/5 px-6 py-5 dark:border-white/10"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Aktivitas terbaru</p><h2 class="mt-1 text-xl font-bold tracking-tight text-ink-950 dark:text-porcelain-50">Sesi tes terkini</h2></div><a href="{{ route('admin.recap.index') }}" class="text-sm font-semibold text-brand-600 transition hover:text-brand-500 dark:text-brand-300">Lihat semua</a></div>
                @if ($recent->isEmpty())
                    <div class="p-10 text-center"><p class="font-semibold text-ink-800 dark:text-porcelain-100">Belum ada calon mahasiswa yang mengerjakan tes</p><p class="mt-1 text-sm text-ink-500 dark:text-porcelain-300/70">Sesi terbaru akan muncul otomatis di sini.</p></div>
                @else
                    <table class="w-full table-fixed divide-y divide-black/5 text-sm dark:divide-white/10">
                        <thead class="bg-porcelain-50 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-ink-500 dark:bg-ink-950/50 dark:text-porcelain-300/70">
                            <tr>
                                <th class="w-[18%] px-3 py-3 sm:w-28 sm:px-6">Kode</th>
                                <th class="w-[31%] px-3 py-3 sm:w-[24%] sm:px-6">Calon mahasiswa</th>
                                <th class="w-[20%] px-3 py-3 sm:w-32 sm:px-6">Tanggal</th>
                                <th class="w-[23%] px-3 py-3 sm:px-6">Rekomendasi</th>
                                <th class="w-[8%] px-3 py-3 text-right sm:w-20 sm:px-6">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/10">
                            @foreach ($recent as $assessment)
                                <tr class="dashboard-history-row text-ink-700 dark:text-porcelain-200">
                                    <td class="truncate px-3 py-4 font-mono text-xs sm:px-6" title="{{ $assessment->code }}">{{ $assessment->code }}</td>
                                    <td class="truncate px-3 py-4 font-semibold sm:px-6" title="{{ $assessment->full_name }}">{{ $assessment->full_name }}</td>
                                    <td class="truncate px-3 py-4 text-xs text-ink-500 dark:text-porcelain-300/70 sm:px-6 sm:text-sm" title="{{ $assessment->created_at->translatedFormat('d M Y') }}">{{ $assessment->created_at->translatedFormat('d M Y') }}</td>
                                    <td class="truncate px-3 py-4 sm:px-6" title="{{ $assessment->recommendedProgram?->full_name ?? '-' }}">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</td>
                                    <td class="px-3 py-4 text-right sm:px-6"><x-icon-button :href="route('admin.recap.show', $assessment)" color="brand" title="Lihat detail tes"><x-icon.eye /></x-icon-button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
