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
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Tambah Gelombang
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
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

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
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
                                        <a href="{{ route('admin.recap.index', ['period' => $period->id]) }}"
                                           class="font-medium text-gray-600 hover:underline dark:text-gray-300">Rekap</a>

                                        <a href="{{ route('admin.periods.edit', $period) }}"
                                           class="ms-3 font-medium text-indigo-600 hover:underline dark:text-indigo-400">Ubah</a>

                                        @if ($period->assessments_count === 0)
                                            <form action="{{ route('admin.periods.destroy', $period) }}" method="POST" class="ms-3 inline"
                                                  onsubmit="return confirm('Hapus gelombang {{ $period->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
                                            </form>
                                        @else
                                            <span class="ms-3 text-xs text-gray-400" title="Sudah dipakai sesi tes">Terpakai</span>
                                        @endif
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
        </div>
    </div>
</x-app-layout>
