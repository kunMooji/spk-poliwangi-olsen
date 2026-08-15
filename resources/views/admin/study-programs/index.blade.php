<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Program Studi</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $activeTotal }} aktif dari {{ $total }} prodi &middot; menjadi alternatif keputusan CoCoSo
                </p>
            </div>
            <a href="{{ route('admin.study-programs.create') }}"
               class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                Tambah Prodi
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

            <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                <div class="min-w-64 flex-1">
                    <x-input-label for="q" value="Cari" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full"
                                  :value="request('q')" placeholder="Kode, nama, atau jurusan" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
                <button type="submit"
                        class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                    Terapkan
                </button>
                @if (request()->hasAny(['q', 'status']))
                    <a href="{{ route('admin.study-programs.index') }}"
                       class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Reset
                    </a>
                @endif
            </form>

            @if (! $programs->isEmpty())
                <div class="flex justify-end">
                    <x-list-view-toggle />
                </div>
            @endif

            <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                @if ($programs->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">Tidak ada program studi yang cocok.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Kode</th>
                                    <th class="px-6 py-3">Program Studi</th>
                                    <th class="px-6 py-3">Jurusan</th>
                                    <th class="px-6 py-3">Kode Holland</th>
                                    <th class="px-6 py-3">Serapan Kerja</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($programs as $program)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">{{ $program->code }}</td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $program->full_name }}</td>
                                        <td class="px-6 py-4">{{ $program->department ?? '-' }}</td>
                                        <td class="px-6 py-4 font-semibold">{{ $program->holland_code }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ number_format($program->employment_percent, 1) }}%</td>
                                        <td class="px-6 py-4">
                                            @if ($program->is_active)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                            @else
                                                <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-1">
                                                <x-icon-button :href="route('admin.study-programs.edit', $program)" color="brand" title="Ubah">
                                                    <x-icon.pencil />
                                                </x-icon-button>

                                                <form action="{{ route('admin.study-programs.destroy', $program) }}" method="POST"
                                                      onsubmit="return confirm('Hapus program studi {{ $program->code }}?')">
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
                        {{ $programs->links() }}
                    </div>
                @endif
            </div>

            @if (! $programs->isEmpty())
                <div x-show="view === 'card'" x-cloak>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($programs as $program)
                            @php($programColor = \App\Support\Riasec::color(substr($program->holland_code ?? 'C', 0, 1)))
                            <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                                <div class="relative overflow-hidden p-5 text-white"
                                     style="background-color: {{ $programColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                    <svg class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 text-white/10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                                    </svg>

                                    <div class="relative flex items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-start gap-2.5">
                                            <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                                                </svg>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-base font-semibold">{{ $program->full_name }}</p>
                                                <p class="mt-0.5 truncate text-xs text-white/80">{{ $program->department ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <span class="whitespace-nowrap rounded-full bg-white/20 px-2.5 py-1 font-mono text-[11px] font-medium backdrop-blur-sm">
                                            {{ $program->code }}
                                        </span>
                                    </div>
                                </div>

                                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 p-5 pb-3 text-sm">
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Kode Holland</dt>
                                        <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $program->holland_code }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Serapan Kerja</dt>
                                        <dd class="text-gray-700 dark:text-gray-300">{{ number_format($program->employment_percent, 1) }}%</dd>
                                    </div>
                                </dl>

                                <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                                    @if ($program->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                    @endif

                                    <div class="inline-flex items-center gap-1">
                                        <x-icon-button :href="route('admin.study-programs.edit', $program)" color="brand" title="Ubah">
                                            <x-icon.pencil />
                                        </x-icon-button>

                                        <form action="{{ route('admin.study-programs.destroy', $program) }}" method="POST"
                                              onsubmit="return confirm('Hapus program studi {{ $program->code }}?')">
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
                        {{ $programs->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
