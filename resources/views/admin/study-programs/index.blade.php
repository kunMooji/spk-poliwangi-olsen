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
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Tambah Prodi
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
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

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
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
                                            <a href="{{ route('admin.study-programs.edit', $program) }}"
                                               class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Ubah</a>

                                            <form action="{{ route('admin.study-programs.destroy', $program) }}" method="POST" class="ms-3 inline"
                                                  onsubmit="return confirm('Hapus program studi {{ $program->code }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
                                            </form>
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
        </div>
    </div>
</x-app-layout>
