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
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Tambah Kriteria
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
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

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
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
                                    <td class="px-6 py-4">
                                        {{ $criterion->source_label }}
                                        @if ($criterion->subject)
                                            <span class="text-gray-400">&middot; {{ \App\Support\Riasec::subjectLabel($criterion->subject) }}</span>
                                        @endif
                                    </td>
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
                                        <a href="{{ route('admin.criteria.edit', $criterion) }}"
                                           class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Ubah</a>

                                        <form action="{{ route('admin.criteria.destroy', $criterion) }}" method="POST" class="ms-3 inline"
                                              onsubmit="return confirm('Hapus kriteria {{ $criterion->code }}? Total bobot perlu disesuaikan kembali.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
                                        </form>
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
        </div>
    </div>
</x-app-layout>
