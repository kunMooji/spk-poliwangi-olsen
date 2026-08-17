<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Kriteria &amp; Bobot</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Perubahan bobot hanya berlaku untuk tes berikutnya; hasil lama memakai bobot yang tersimpan saat perhitungan.
                </p>
            </div>
            <a href="{{ route('admin.criteria.create') }}"
               class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                Tambah Kriteria
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

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

            <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
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
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($criteria as $criterion)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">{{ $criterion->code }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $criterion->name }}</td>
                                    <td class="px-6 py-4">{{ $criterion->source_label }}</td>
                                    <td class="px-6 py-4">{{ $criterion->isBenefit() ? 'Benefit' : 'Cost' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-semibold">{{ number_format($criterion->weight, 4) }}</td>
                                    <td class="px-6 py-4">
                                        @if ($criterion->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <x-icon-button :href="route('admin.criteria.edit', $criterion)" color="brand" title="Ubah">
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
                        <tfoot class="bg-gray-50 dark:bg-gray-900/50">
                            <tr class="text-gray-700 dark:text-gray-300">
                                <td colspan="4" class="px-6 py-3 text-right text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
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
                        <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
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
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Jenis</dt>
                                    <dd class="text-gray-700 dark:text-gray-300">{{ $criterion->isBenefit() ? 'Benefit' : 'Cost' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Bobot</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($criterion->weight, 4) }}</dd>
                                </div>
                            </dl>

                            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                                @if ($criterion->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                @else
                                    <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                @endif

                                <div class="inline-flex items-center gap-1">
                                    <x-icon-button :href="route('admin.criteria.edit', $criterion)" color="brand" title="Ubah">
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

                <div class="mt-4 flex items-center justify-end rounded-xl bg-white px-5 py-3 text-sm shadow-sm dark:bg-gray-800">
                    <span class="text-gray-500 dark:text-gray-400">Total bobot kriteria aktif&nbsp;</span>
                    <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalWeight, 4) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
