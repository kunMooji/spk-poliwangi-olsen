<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Kriteria &amp; Bobot</h2></x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-none px-5 sm:px-8 lg:px-10 xl:px-12"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table', dialog: @js(old('_dialog')) }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))"
             x-effect="document.documentElement.style.overflow = dialog ? 'hidden' : ''; document.body.style.overflow = dialog ? 'hidden' : ''">
            <x-flash />

            <x-admin-panel-hero eyebrow="Parameter keputusan" title="Kriteria & Bobot" description="Atur parameter dan bobot yang membentuk hasil rekomendasi CoCoSo.">
                <x-slot:action>
                    <button type="button" @click="dialog = 'create'" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-950/30 transition hover:-translate-y-0.5 hover:bg-brand-400"><x-heroicon-o-plus class="h-4 w-4" /> Tambah Kriteria</button>
                </x-slot:action>
                <x-slot:content>
                <div class="space-y-4">

            @if (abs($totalWeight - 1) > 0.0001)
                <x-alert type="warning">
                    Total bobot kriteria aktif saat ini <span class="font-semibold">{{ number_format($totalWeight, 4) }}</span>,
                    seharusnya <span class="font-semibold">1.0000</span>. Perhitungan tetap berjalan, namun nilai
                    S<sub>i</sub> dan P<sub>i</sub> tidak berada pada skala yang seharusnya.
                </x-alert>
            @else
                <x-alert type="success">
                    Total bobot kriteria aktif <span class="font-semibold">{{ number_format($totalWeight, 4) }}</span> &mdash; sudah sesuai.
                </x-alert>
            @endif

            {{-- SNBP: rerata seluruh mapel paling sedikit 50% dari blok rapor,
                 mapel pendukung paling banyak 50%. Rasio dihitung terhadap blok
                 rapor saja karena RIASEC, prioritas, dan tracer tidak dikenal SNBP. --}}
            @if ($raporShare === null)
                <x-alert type="warning">
                    Belum ada kriteria bersumber nilai rapor yang aktif. Skema SNBP mensyaratkan rerata rapor
                    seluruh mata pelajaran ikut diperhitungkan.
                </x-alert>
            @elseif ($raporShare < 50)
                <x-alert type="warning">
                    Komponen <span class="font-semibold">Rerata Rapor Seluruh Mapel</span> hanya memegang
                    <span class="font-semibold">{{ number_format($raporShare, 1) }}%</span> dari blok nilai rapor.
                    SNBP mensyaratkan paling sedikit 50%, dengan mapel pendukung paling banyak 50%.
                </x-alert>
            @else
                <x-alert type="success">
                    Komposisi blok nilai rapor sesuai SNBP &mdash; rerata seluruh mapel
                    <span class="font-semibold">{{ number_format($raporShare, 1) }}%</span>, mapel pendukung
                    <span class="font-semibold">{{ number_format(100 - $raporShare, 1) }}%</span>.
                </x-alert>
            @endif

            <div class="flex justify-end">
                <x-list-view-toggle />
            </div>

            <div x-show="view === 'table'" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10">
                <div class="flex items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-6">
                    <div>
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-200">Daftar parameter</p>
                        <h2 class="mt-1 text-base font-bold text-ink-950 dark:text-white">Kriteria aktif dan bobot</h2>
                    </div>
                    <span @class([
                        'rounded-md px-2.5 py-1 text-xs font-bold',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200' => abs($totalWeight - 1) <= 0.0001,
                        'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-100' => abs($totalWeight - 1) > 0.0001,
                    ])>Total {{ number_format($totalWeight, 4) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-100 text-sm dark:divide-white/10">
                        <thead class="bg-brand-50 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-ink-500 dark:bg-black/15 dark:text-porcelain-200/55">
                            <tr>
                                <th class="px-6 py-3">Kode</th>
                                <th class="px-6 py-3">Nama Kriteria</th>
                                <th class="px-6 py-3">Sumber Nilai</th>
                                <th class="px-6 py-3">Jenis</th>
                                <th class="px-6 py-3 text-right">Bobot</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-100 dark:divide-white/10">
                            @foreach ($criteria as $criterion)
                                <tr class="text-ink-700 transition hover:bg-brand-50/70 dark:text-porcelain-100/80 dark:hover:bg-white/[0.04]">
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-ink-400 dark:text-porcelain-200/45">{{ $criterion->code }}</td>
                                    <td class="px-6 py-4 font-semibold text-ink-950 dark:text-white">{{ $criterion->name }}</td>
                                    <td class="px-6 py-4">{{ $criterion->source_label }}</td>
                                    <td class="px-6 py-4">{{ $criterion->isBenefit() ? 'Benefit' : 'Cost' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-mono font-bold">{{ number_format($criterion->weight, 4) }}</td>
                                    <td class="px-6 py-4">
                                        @if ($criterion->is_active)
                                            <span class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200">Aktif</span>
                                        @else
                                            <span class="rounded-md bg-ink-100 px-2 py-1 text-xs font-semibold text-ink-500 dark:bg-white/10 dark:text-porcelain-200/65">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <x-icon-button @click="dialog = 'edit-{{ $criterion->id }}'" color="brand" title="Ubah">
                                                <x-icon.pencil />
                                            </x-icon-button>

                                            <form action="{{ route('admin.criteria.destroy', $criterion) }}" method="POST"
                                                  onsubmit="return confirm('Hapus kriteria {{ $criterion->code }}? Total bobot perlu disesuaikan kembali.')">
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
                        <tfoot class="bg-brand-50/70 dark:bg-black/15">
                            <tr class="text-ink-700 dark:text-porcelain-200">
                                <td colspan="4" class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-ink-500 dark:text-porcelain-200/55">
                                    Total bobot kriteria aktif
                                </td>
                                <td class="px-6 py-3 text-right font-bold">{{ number_format($totalWeight, 4) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div x-show="view === 'card'" x-cloak>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($criteria as $criterion)
                        @php
                            $criterionColor = match ($criterion->source) {
                                'rapor_average' => '#0284c7',
                                'support_subject' => '#4f46e5',
                                'riasec' => '#c026d3',
                                'priority' => '#d97706',
                                'tracer' => '#059669',
                                default => '#6b7280',
                            };
                            $isRaporSource = in_array($criterion->source, \App\Models\Criteria::RAPOR_SOURCES, true);
                        @endphp
                        <div class="flex flex-col overflow-hidden rounded-xl border border-brand-100 bg-white shadow-sm shadow-ink-950/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-ink-950/10 dark:border-white/10 dark:bg-white/[0.06]">
                            <div class="relative overflow-hidden p-5 text-white"
                                 style="background-color: {{ $criterionColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                <svg class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 text-white/10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    @if ($isRaporSource)
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                    @elseif ($criterion->source === 'riasec')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                                    @elseif ($criterion->source === 'priority')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    @endif
                                </svg>

                                <div class="relative flex items-start justify-between gap-2">
                                    <div class="flex min-w-0 items-start gap-2.5">
                                        <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                @if ($isRaporSource)
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                                @elseif ($criterion->source === 'riasec')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                                                @elseif ($criterion->source === 'priority')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                @endif
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-semibold">{{ $criterion->name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-white/80">{{ $criterion->source_label }}</p>
                                        </div>
                                    </div>
                                    <span class="whitespace-nowrap rounded-full bg-white/20 px-2.5 py-1 font-mono text-[11px] font-medium backdrop-blur-sm">
                                        {{ $criterion->code }}
                                    </span>
                                </div>
                            </div>

                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 p-5 pb-3 text-sm">
                                <div>
                                    <dt class="text-xs text-ink-500 dark:text-porcelain-200/65">Jenis</dt>
                                    <dd class="text-ink-700 dark:text-porcelain-100">{{ $criterion->isBenefit() ? 'Benefit' : 'Cost' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-ink-500 dark:text-porcelain-200/65">Bobot</dt>
                                    <dd class="font-mono font-bold text-ink-950 dark:text-white">{{ number_format($criterion->weight, 4) }}</dd>
                                </div>
                            </dl>

                            <div class="flex items-center justify-between border-t border-brand-100 px-5 py-3 dark:border-white/10">
                                @if ($criterion->is_active)
                                    <span class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200">Aktif</span>
                                @else
                                    <span class="rounded-md bg-ink-100 px-2 py-1 text-xs font-semibold text-ink-500 dark:bg-white/10 dark:text-porcelain-200/65">Nonaktif</span>
                                @endif

                                <div class="inline-flex items-center gap-1">
                                    <x-icon-button @click="dialog = 'edit-{{ $criterion->id }}'" color="brand" title="Ubah">
                                        <x-icon.pencil />
                                    </x-icon-button>

                                    <form action="{{ route('admin.criteria.destroy', $criterion) }}" method="POST"
                                          onsubmit="return confirm('Hapus kriteria {{ $criterion->code }}? Total bobot perlu disesuaikan kembali.')">
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

                <div class="mt-4 flex items-center justify-end rounded-xl border border-brand-100 bg-white px-5 py-3 text-sm shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06]">
                    <span class="text-ink-500 dark:text-porcelain-200/65">Total bobot kriteria aktif&nbsp;</span>
                    <span class="font-mono font-bold text-ink-950 dark:text-white">{{ number_format($totalWeight, 4) }}</span>
                </div>
            </div>
                </div>
                </x-slot:content>
            </x-admin-panel-hero>

            <div x-show="dialog === 'create'" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-ink-950/55 p-4 backdrop-blur-sm" @keydown.escape.window="dialog = null" role="dialog" aria-modal="true" aria-labelledby="create-criterion-title">
                <div @click.outside="dialog = null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" class="flex h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-ink-900">
                    <div class="flex shrink-0 items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-7">
                        <div>
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-200">Parameter keputusan</p>
                            <h2 id="create-criterion-title" class="mt-1 text-lg font-bold text-ink-950 dark:text-white">Tambah Kriteria</h2>
                        </div>
                        <button type="button" @click="dialog = null" class="rounded-lg p-2 text-ink-400 transition hover:bg-brand-50 hover:text-ink-900 dark:text-porcelain-200/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Tutup dialog">
                            <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-7" data-lenis-prevent>
                        <form method="POST" action="{{ route('admin.criteria.store') }}">
                            @csrf
                            @include('admin.criteria.form', ['isModal' => true, 'dialogKey' => 'create'])
                        </form>
                    </div>
                </div>
            </div>

            @foreach ($criteria as $editCriterion)
                <div x-show="dialog === 'edit-{{ $editCriterion->id }}'" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-ink-950/55 p-4 backdrop-blur-sm" @keydown.escape.window="dialog = null" role="dialog" aria-modal="true" aria-labelledby="edit-criterion-title-{{ $editCriterion->id }}">
                    <div @click.outside="dialog = null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" class="flex h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-ink-900">
                        <div class="flex shrink-0 items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-7">
                            <div>
                                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-200">Parameter keputusan</p>
                                <h2 id="edit-criterion-title-{{ $editCriterion->id }}" class="mt-1 text-lg font-bold text-ink-950 dark:text-white">Ubah Kriteria &mdash; {{ $editCriterion->code }}</h2>
                            </div>
                            <button type="button" @click="dialog = null" class="rounded-lg p-2 text-ink-400 transition hover:bg-brand-50 hover:text-ink-900 dark:text-porcelain-200/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Tutup dialog">
                                <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-7" data-lenis-prevent>
                            <form method="POST" action="{{ route('admin.criteria.update', $editCriterion) }}">
                                @csrf
                                @method('PUT')
                                @include('admin.criteria.form', [
                                    'criterion' => $editCriterion,
                                    'isModal' => true,
                                    'dialogKey' => 'edit-'.$editCriterion->id,
                                ])
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
