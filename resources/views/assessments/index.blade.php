@use('App\Support\Riasec')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-[10px] font-bold uppercase tracking-wide text-ink-900 dark:text-porcelain-50 sm:text-sm">Riwayat tes saya</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-none flex-col gap-6 px-5 sm:px-8 lg:px-10 xl:px-12"
             x-data="{ view: localStorage.getItem('spk-student-history-view') || 'table' }"
             x-init="$watch('view', value => localStorage.setItem('spk-student-history-view', value))">
            <x-flash />

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Arsip pribadi</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 dark:text-porcelain-50">Riwayat tes saya</h1>
                </div>
                <a href="{{ route('assessments.create') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-porcelain-50 shadow-sm transition duration-200 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-md active:scale-[0.98]">
                    <x-heroicon-o-plus class="h-4 w-4" aria-hidden="true" />
                    Mulai tes baru
                </a>
            </div>

            @if ($assessments->isEmpty())
                <div class="mt-4 rounded-2xl border border-dashed border-black/10 bg-white px-6 py-14 text-center shadow-sm dark:border-white/10 dark:bg-ink-900/60">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                        <x-heroicon-o-clipboard-document-check class="h-7 w-7" aria-hidden="true" />
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-ink-900 dark:text-porcelain-50">Belum ada riwayat tes</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-ink-500 dark:text-porcelain-300/70">Mulai tes pertama untuk melihat profil RIASEC dan rekomendasi program studi Anda di sini.</p>
                    <a href="{{ route('assessments.create') }}"
                       class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-porcelain-50 transition hover:bg-brand-700 active:scale-[0.98]">
                        Mulai tes pertama
                        <x-heroicon-o-arrow-right class="h-4 w-4" aria-hidden="true" />
                    </a>
                </div>
            @else
                <div class="mt-4 flex items-center justify-between gap-4">
                    <p class="text-sm text-ink-500 dark:text-porcelain-300/70">{{ $assessments->total() }} sesi tersimpan</p>
                    <x-list-view-toggle />
                </div>

                <div x-show="view === 'table'" class="mt-4 overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-ink-900/60">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                            <thead class="bg-porcelain-50 text-left text-[10px] font-bold uppercase tracking-[0.16em] text-ink-500 dark:bg-ink-950/40 dark:text-porcelain-300/70">
                                <tr>
                                    <th class="px-6 py-3.5">Kode</th>
                                    <th class="px-6 py-3.5">Tanggal</th>
                                    <th class="px-6 py-3.5">Holland</th>
                                    <th class="px-6 py-3.5">Rekomendasi</th>
                                    <th class="px-6 py-3.5">Status</th>
                                    <th class="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5 dark:divide-white/10">
                                @foreach ($assessments as $assessment)
                                    <tr class="text-ink-700 transition hover:bg-brand-50/50 dark:text-porcelain-300 dark:hover:bg-brand-500/10">
                                        <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-bold text-ink-600 dark:text-porcelain-200">{{ $assessment->code }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $assessment->created_at->translatedFormat('d M Y, H:i') }}</td>
                                        <td class="px-6 py-4"><span class="font-mono font-bold tracking-wider">{{ $assessment->holland_code ?? '-' }}</span></td>
                                        <td class="px-6 py-4 font-medium">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            @if ($assessment->isCompleted())
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Belum selesai</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            @if ($assessment->isCompleted())
                                                <a href="{{ route('assessments.result', $assessment) }}" class="font-semibold text-brand-600 transition hover:text-brand-500 dark:text-brand-300">Lihat hasil</a>
                                            @else
                                                <a href="{{ route('assessments.questionnaire', $assessment) }}" class="font-semibold text-amber-700 transition hover:text-amber-600 dark:text-amber-300">Lanjutkan</a>
                                            @endif
                                            <form action="{{ route('assessments.destroy', $assessment) }}" method="POST" class="ms-3 inline" onsubmit="return confirm('Hapus data tes {{ $assessment->code }}? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-semibold text-rose-600 transition hover:text-rose-500 dark:text-rose-400">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-black/5 px-6 py-4 dark:border-white/10">{{ $assessments->links() }}</div>
                </div>

                <div x-show="view === 'card'" x-cloak class="mt-4">
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($assessments as $assessment)
                            @php($color = $assessment->dominant_type ? Riasec::color($assessment->dominant_type) : '#64748b')
                            <article class="group flex min-h-72 flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm shadow-ink-950/5 transition duration-300 ease-brand-out hover:-translate-y-1 hover:shadow-lg hover:shadow-ink-950/10 dark:border-white/10 dark:bg-ink-900/60">
                                <div class="relative overflow-hidden px-5 py-5 text-white" style="background-color: {{ $color }}; background-image: linear-gradient(135deg, rgba(255,255,255,.22), rgba(5,14,19,.30));">
                                    <span class="pointer-events-none absolute -right-2 -top-7 select-none font-display text-8xl font-extrabold leading-none text-white/10">{{ $assessment->dominant_type ?? '?' }}</span>
                                    <div class="relative flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">Kode Holland</p>
                                            <p class="mt-1 font-mono text-2xl font-bold tracking-[0.15em]">{{ $assessment->holland_code ?? '-' }}</p>
                                        </div>
                                        @if ($assessment->isCompleted())
                                            <span class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">Selesai</span>
                                        @else
                                            <span class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">Belum selesai</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col p-5">
                                    <p class="font-mono text-[11px] font-bold text-ink-400 dark:text-porcelain-400">{{ $assessment->code }}</p>
                                    <h3 class="mt-3 text-base font-bold leading-snug text-ink-900 dark:text-porcelain-50">{{ $assessment->recommendedProgram?->full_name ?? 'Tes belum memiliki rekomendasi' }}</h3>
                                    <p class="mt-2 flex items-center gap-1.5 text-sm text-ink-500 dark:text-porcelain-300/70">
                                        <x-heroicon-o-calendar-days class="h-4 w-4 shrink-0" aria-hidden="true" />
                                        {{ $assessment->created_at->translatedFormat('d F Y, H:i') }}
                                    </p>

                                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-black/5 pt-4 dark:border-white/10">
                                        @if ($assessment->isCompleted())
                                            <a href="{{ route('assessments.result', $assessment) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 transition hover:text-brand-500 dark:text-brand-300">
                                                Lihat hasil
                                                <x-heroicon-o-arrow-right class="h-4 w-4" aria-hidden="true" />
                                            </a>
                                        @else
                                            <a href="{{ route('assessments.questionnaire', $assessment) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 transition hover:text-amber-600 dark:text-amber-300">
                                                Lanjutkan
                                                <x-heroicon-o-arrow-right class="h-4 w-4" aria-hidden="true" />
                                            </a>
                                        @endif
                                        <form action="{{ route('assessments.destroy', $assessment) }}" method="POST" onsubmit="return confirm('Hapus data tes {{ $assessment->code }}? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500/40 dark:text-rose-400 dark:hover:bg-rose-900/30" title="Hapus tes">
                                                <x-heroicon-o-trash class="h-4 w-4" aria-hidden="true" />
                                                <span class="sr-only">Hapus {{ $assessment->code }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $assessments->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
