<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Rekap Hasil Tes</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $totalCompleted }} tes selesai dari {{ $totalAll }} sesi yang pernah dibuat calon mahasiswa.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <x-input-label for="q" value="Cari" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full"
                                  :value="request('q')" placeholder="Nama, kode tes, sekolah, email" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                        <option value="ongoing" @selected(request('status') === 'ongoing')>Belum selesai</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="dominant" value="Tipe Dominan" />
                    <select id="dominant" name="dominant"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        @foreach ($dimensions as $code => $label)
                            <option value="{{ $code }}" @selected(request('dominant') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="program" value="Rekomendasi" />
                    <select id="program" name="program"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua prodi</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(request('program') == $program->id)>{{ $program->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="match" value="Pilihan Pertama" />
                    <select id="match" name="match"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="sesuai" @selected(request('match') === 'sesuai')>Sesuai rekomendasi</option>
                        <option value="beda" @selected(request('match') === 'beda')>Berbeda</option>
                    </select>
                </div>

                <div class="flex items-end gap-3 sm:col-span-2 lg:col-span-6">
                    <button type="submit"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                        Terapkan Filter
                    </button>
                    @if (request()->hasAny(['q', 'status', 'dominant', 'program', 'match']))
                        <a href="{{ route('admin.recap.index') }}"
                           class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                @if ($assessments->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">Belum ada data tes yang cocok dengan filter.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Kode</th>
                                    <th class="px-6 py-3">Calon Mahasiswa</th>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3">Holland</th>
                                    <th class="px-6 py-3">Rekomendasi</th>
                                    <th class="px-6 py-3">Pilihan Pertama</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($assessments as $assessment)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">{{ $assessment->code }}</td>
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $assessment->full_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $assessment->user?->email ?? '-' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $assessment->created_at->translatedFormat('d M Y') }}</td>
                                        <td class="px-6 py-4 font-semibold">{{ $assessment->holland_code ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            @if (! $assessment->isCompleted())
                                                <span class="text-gray-400">-</span>
                                            @elseif ($assessment->matches_preference)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Sesuai</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Berbeda</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($assessment->isCompleted())
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>
                                            @else
                                                <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Belum selesai</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <a href="{{ route('admin.recap.show', $assessment) }}"
                                               class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Detail</a>

                                            <form action="{{ route('admin.recap.destroy', $assessment) }}" method="POST" class="ms-3 inline"
                                                  onsubmit="return confirm('Hapus data tes {{ $assessment->code }} milik {{ $assessment->full_name }}?')">
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
                        {{ $assessments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
