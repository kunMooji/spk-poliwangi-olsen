<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Tracer Study</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-4 px-5 sm:px-8 lg:px-10 xl:px-12"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

            <x-admin-panel-hero eyebrow="Data prospek kerja" title="Tracer Study" description="Perbarui data alumni dan serapan kerja sebagai dasar kriteria prospek kerja." />

            <div class="flex justify-end">
                <x-list-view-toggle />
            </div>

            <form method="POST" action="{{ route('admin.tracer.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Kedua tampilan berbagi satu <form>. Input pada tampilan yang
                     sedang tersembunyi dinonaktifkan (:disabled) supaya tidak ikut
                     terkirim dan menimpa nilai yang sedang diedit di tampilan aktif. --}}
                <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Program Studi</th>
                                    <th class="px-6 py-3">Jumlah Alumni</th>
                                    <th class="px-6 py-3">Terserap Kerja</th>
                                    <th class="px-6 py-3">Tahun</th>
                                    <th class="px-6 py-3 text-right">Serapan Saat Ini</th>
                                    <th class="px-6 py-3">Diperbarui</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($programs as $program)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="px-6 py-3">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $program->full_name }}</p>
                                            <p class="font-mono text-xs text-gray-400">{{ $program->code }}</p>
                                        </td>
                                        <td class="px-6 py-3">
                                            <x-text-input type="number" min="0" class="block w-28"
                                                          x-bind:disabled="view !== 'table'"
                                                          :name="'programs['.$program->id.'][alumni_count]'"
                                                          :value="old('programs.'.$program->id.'.alumni_count', $program->alumni_count)" required />
                                            <x-input-error :messages="$errors->get('programs.'.$program->id.'.alumni_count')" class="mt-1" />
                                        </td>
                                        <td class="px-6 py-3">
                                            <x-text-input type="number" min="0" class="block w-28"
                                                          x-bind:disabled="view !== 'table'"
                                                          :name="'programs['.$program->id.'][employed_count]'"
                                                          :value="old('programs.'.$program->id.'.employed_count', $program->employed_count)" required />
                                            <x-input-error :messages="$errors->get('programs.'.$program->id.'.employed_count')" class="mt-1" />
                                        </td>
                                        <td class="px-6 py-3">
                                            <x-text-input type="number" min="2000" max="{{ date('Y') }}" class="block w-28"
                                                          x-bind:disabled="view !== 'table'"
                                                          :name="'programs['.$program->id.'][tracer_year]'"
                                                          :value="old('programs.'.$program->id.'.tracer_year', $program->tracer_year)" />
                                            <x-input-error :messages="$errors->get('programs.'.$program->id.'.tracer_year')" class="mt-1" />
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right font-semibold">
                                            {{ number_format($program->employment_percent, 1) }}%
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $program->tracer_updated_at?->translatedFormat('d M Y') ?? 'Belum pernah' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="view === 'card'" x-cloak class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($programs as $program)
                        @php
                            $tracerColor = match (true) {
                                $program->employment_percent >= 80 => '#059669',
                                $program->employment_percent >= 60 => '#d97706',
                                default => '#e11d48',
                            };
                        @endphp
                        <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                            <div class="relative overflow-hidden p-5 text-white"
                                 style="background-color: {{ $tracerColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                <svg class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 text-white/10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>

                                <div class="relative flex items-start justify-between gap-2">
                                    <div class="flex min-w-0 items-start gap-2.5">
                                        <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-semibold">{{ $program->full_name }}</p>
                                            <p class="mt-0.5 font-mono text-xs text-white/80">{{ $program->code }}</p>
                                        </div>
                                    </div>
                                    <span class="whitespace-nowrap rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">
                                        {{ number_format($program->employment_percent, 1) }}%
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 p-5 pb-3">
                                <div>
                                    <x-input-label value="Jumlah Alumni" class="text-xs" />
                                    <x-text-input type="number" min="0" class="mt-1 block w-full"
                                                  x-bind:disabled="view !== 'card'"
                                                  :name="'programs['.$program->id.'][alumni_count]'"
                                                  :value="old('programs.'.$program->id.'.alumni_count', $program->alumni_count)" required />
                                </div>
                                <div>
                                    <x-input-label value="Terserap Kerja" class="text-xs" />
                                    <x-text-input type="number" min="0" class="mt-1 block w-full"
                                                  x-bind:disabled="view !== 'card'"
                                                  :name="'programs['.$program->id.'][employed_count]'"
                                                  :value="old('programs.'.$program->id.'.employed_count', $program->employed_count)" required />
                                </div>
                                <div class="col-span-2">
                                    <x-input-label value="Tahun" class="text-xs" />
                                    <x-text-input type="number" min="2000" max="{{ date('Y') }}" class="mt-1 block w-full"
                                                  x-bind:disabled="view !== 'card'"
                                                  :name="'programs['.$program->id.'][tracer_year]'"
                                                  :value="old('programs.'.$program->id.'.tracer_year', $program->tracer_year)" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <span>Serapan saat ini <strong class="text-gray-900 dark:text-gray-100">{{ number_format($program->employment_percent, 1) }}%</strong></span>
                                <span>{{ $program->tracer_updated_at?->translatedFormat('d M Y') ?? 'Belum pernah' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-end">
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        Simpan Data Tracer
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
