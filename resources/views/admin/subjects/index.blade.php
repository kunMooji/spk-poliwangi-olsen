<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Mata Pelajaran</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Daftar mata pelajaran yang dapat ditetapkan sebagai mapel pendukung program studi.
                    Tambahkan mapel produktif SMK sesuai konsentrasi keahlian yang relevan.
                </p>
            </div>
            <a href="{{ route('admin.subjects.create') }}"
               class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                Tambah Mata Pelajaran
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Nama</th>
                                <th class="px-6 py-3">Jenjang</th>
                                <th class="px-6 py-3">Kelompok</th>
                                <th class="px-6 py-3 text-right">Dipakai Prodi</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($subjects as $subject)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $subject->name }}</td>
                                    <td class="px-6 py-4">{{ $subject->education_level === 'umum' ? 'Umum' : $subject->education_level }}</td>
                                    <td class="px-6 py-4">{{ $subject->group ?: '—' }}</td>
                                    <td class="px-6 py-4 text-right font-semibold">{{ $subject->study_programs_count }}</td>
                                    <td class="px-6 py-4">
                                        @if ($subject->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <x-icon-button :href="route('admin.subjects.edit', $subject)" color="brand" title="Ubah">
                                                <x-icon.pencil />
                                            </x-icon-button>

                                            <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST"
                                                  onsubmit="return confirm('Hapus mata pelajaran {{ $subject->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-icon-button type="submit" color="rose" title="Hapus">
                                                    <x-icon.trash />
                                                </x-icon-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada mata pelajaran. Tambahkan minimal satu agar dapat ditetapkan sebagai mapel pendukung prodi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
