<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Tracer Study</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Data serapan kerja alumni &mdash; menjadi nilai kriteria C9 pada perhitungan CoCoSo.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('admin.tracer.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
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
                                                          :name="'programs['.$program->id.'][alumni_count]'"
                                                          :value="old('programs.'.$program->id.'.alumni_count', $program->alumni_count)" required />
                                            <x-input-error :messages="$errors->get('programs.'.$program->id.'.alumni_count')" class="mt-1" />
                                        </td>
                                        <td class="px-6 py-3">
                                            <x-text-input type="number" min="0" class="block w-28"
                                                          :name="'programs['.$program->id.'][employed_count]'"
                                                          :value="old('programs.'.$program->id.'.employed_count', $program->employed_count)" required />
                                            <x-input-error :messages="$errors->get('programs.'.$program->id.'.employed_count')" class="mt-1" />
                                        </td>
                                        <td class="px-6 py-3">
                                            <x-text-input type="number" min="2000" max="{{ date('Y') }}" class="block w-28"
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

                <div class="flex items-center justify-end">
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Simpan Data Tracer
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
