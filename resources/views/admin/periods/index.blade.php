<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Gelombang PMB</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Tes baru ditandai gelombang yang sedang aktif. Penandaan itu melekat pada sesi tes, sehingga
                    mengganti gelombang aktif tidak memindahkan tes yang sudah tercatat.
                </p>
            </div>
            <a href="{{ route('admin.periods.create') }}"
               class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                Tambah Gelombang
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

            @if ($current)
                <x-alert type="success">
                    Gelombang aktif saat ini <span class="font-semibold">{{ $current->name }}</span>
                    ({{ $current->academic_year }}) &mdash; {{ $current->range_label }}.
                </x-alert>
            @else
                <x-alert type="warning">
                    Belum ada gelombang yang aktif. Tes tetap dapat dikerjakan, namun hasilnya tidak tertandai
                    gelombang mana pun sehingga tidak muncul saat rekap disaring per gelombang.
                </x-alert>
            @endif

            @if (! $periods->isEmpty())
                <div class="flex justify-end">
                    <x-list-view-toggle />
                </div>
            @endif

            <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Nama Gelombang</th>
                                <th class="px-6 py-3">Tahun Akademik</th>
                                <th class="px-6 py-3">Rentang Tanggal</th>
                                <th class="px-6 py-3 text-right">Jumlah Tes</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($periods as $period)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $period->name }}
                                        @if ($period->description)
                                            <span class="mt-0.5 block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                {{ $period->description }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $period->academic_year }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-gray-500 dark:text-gray-400">{{ $period->range_label }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-semibold tabular-nums">
                                        {{ $period->assessments_count }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($period->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <x-icon-button :href="route('admin.recap.index', ['period' => $period->id])" color="gray" title="Rekap">
                                                <x-icon.chart-bar />
                                            </x-icon-button>

                                            <x-icon-button :href="route('admin.periods.edit', $period)" color="brand" title="Ubah">
                                                <x-icon.pencil />
                                            </x-icon-button>

                                            @if ($period->assessments_count === 0)
                                                <form action="{{ route('admin.periods.destroy', $period) }}" method="POST"
                                                      onsubmit="return confirm('Hapus gelombang {{ $period->name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-icon-button type="submit" color="rose" title="Hapus">
                                                        <x-icon.trash />
                                                    </x-icon-button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400" title="Sudah dipakai sesi tes">Terpakai</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada gelombang. Tambahkan satu agar rekap dapat disaring per gelombang.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (! $periods->isEmpty())
                <div x-show="view === 'card'" x-cloak class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($periods as $period)
                        @php($periodColor = $period->is_active ? '#059669' : '#64748b')
                        <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                            <div class="relative overflow-hidden p-5 text-white"
                                 style="background-color: {{ $periodColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                <svg class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 text-white/10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>

                                <div class="relative flex items-start justify-between gap-2">
                                    <div class="flex min-w-0 items-start gap-2.5">
                                        <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-semibold">{{ $period->name }}</p>
                                            @if ($period->description)
                                                <p class="mt-0.5 truncate text-xs text-white/80">{{ $period->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="whitespace-nowrap rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">
                                        {{ $period->academic_year }}
                                    </span>
                                </div>
                            </div>

                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 p-5 pb-3 text-sm">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Jumlah Tes</dt>
                                    <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $period->assessments_count }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Rentang Tanggal</dt>
                                    <dd class="text-gray-700 dark:text-gray-300">{{ $period->range_label }}</dd>
                                </div>
                            </dl>

                            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                                @if ($period->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                @else
                                    <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                @endif

                                <div class="inline-flex items-center gap-1">
                                    <x-icon-button :href="route('admin.recap.index', ['period' => $period->id])" color="gray" title="Rekap">
                                        <x-icon.chart-bar />
                                    </x-icon-button>

                                    <x-icon-button :href="route('admin.periods.edit', $period)" color="brand" title="Ubah">
                                        <x-icon.pencil />
                                    </x-icon-button>

                                    @if ($period->assessments_count === 0)
                                        <form action="{{ route('admin.periods.destroy', $period) }}" method="POST"
                                              onsubmit="return confirm('Hapus gelombang {{ $period->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-icon-button type="submit" color="rose" title="Hapus">
                                                <x-icon.trash />
                                            </x-icon-button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400" title="Sudah dipakai sesi tes">Terpakai</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
