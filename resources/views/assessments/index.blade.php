<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Riwayat Tes Saya
            </h2>
            <a href="{{ route('assessments.create') }}"
               class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                Mulai Tes Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                @if ($assessments->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-gray-500 dark:text-gray-400">Anda belum pernah mengikuti tes.</p>
                        <a href="{{ route('assessments.create') }}"
                           class="mt-4 inline-flex items-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                            Mulai Tes Pertama
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Kode</th>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3">Kode Holland</th>
                                    <th class="px-6 py-3">Rekomendasi</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($assessments as $assessment)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">{{ $assessment->code }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $assessment->created_at->translatedFormat('d M Y, H:i') }}</td>
                                        <td class="px-6 py-4 font-semibold">{{ $assessment->holland_code ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            @if ($assessment->isCompleted())
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Belum selesai</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            @if ($assessment->isCompleted())
                                                <a href="{{ route('assessments.result', $assessment) }}"
                                                   class="font-medium text-brand-600 hover:underline dark:text-brand-400">Lihat Hasil</a>
                                            @else
                                                <a href="{{ route('assessments.questionnaire', $assessment) }}"
                                                   class="font-medium text-amber-600 hover:underline dark:text-amber-400">Lanjutkan</a>
                                            @endif

                                            <form action="{{ route('assessments.destroy', $assessment) }}" method="POST" class="ms-3 inline"
                                                  onsubmit="return confirm('Hapus data tes {{ $assessment->code }}? Tindakan ini tidak dapat dibatalkan.')">
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
