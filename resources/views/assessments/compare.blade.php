@use('App\Support\Riasec')

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Bandingkan Hasil Tes</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Lihat apa yang berubah antara dua kali Anda mengerjakan tes &mdash; profil minat, nilai rapor,
                dan program studi yang direkomendasikan.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            @if ($sessions->count() < 2)
                <div class="rounded-xl bg-white p-10 text-center shadow-sm dark:bg-gray-800">
                    <p class="text-gray-500 dark:text-gray-400">
                        Perbandingan baru dapat ditampilkan setelah Anda menyelesaikan
                        <strong>minimal dua kali tes</strong>. Saat ini Anda memiliki
                        {{ $sessions->count() }} hasil tes yang selesai.
                    </p>
                    <a href="{{ route('assessments.create') }}"
                       class="mt-4 inline-block rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        Ikuti Tes
                    </a>
                </div>
            @else
                {{-- Pemilih dua sesi yang dibandingkan --}}
                <form method="GET" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <x-input-label for="a" value="Tes Sebelumnya" />
                        <select id="a" name="a"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}" @selected($left?->id === $session->id)>
                                    {{ $session->completed_at?->translatedFormat('d M Y, H:i') }} &mdash; {{ $session->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="b" value="Tes Pembanding" />
                        <select id="b" name="b"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}" @selected($right?->id === $session->id)>
                                    {{ $session->completed_at?->translatedFormat('d M Y, H:i') }} &mdash; {{ $session->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                                class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                            Bandingkan
                        </button>
                    </div>
                </form>

                @if ($left && $right && $left->id === $right->id)
                    <x-alert type="warning">
                        Anda memilih sesi tes yang sama pada kedua sisi. Pilih dua sesi berbeda agar
                        perubahannya terlihat.
                    </x-alert>
                @elseif ($left && $right)
                    {{-- Ringkasan rekomendasi --}}
                    <section class="grid gap-4 sm:grid-cols-2">
                        @foreach ([['label' => 'Tes Sebelumnya', 'data' => $left, 'tone' => 'gray'], ['label' => 'Tes Pembanding', 'data' => $right, 'tone' => 'indigo']] as $card)
                            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800
                                        {{ $card['tone'] === 'indigo' ? 'ring-2 ring-brand-500' : '' }}">
                                <div class="flex items-baseline justify-between">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                                    <span class="font-mono text-xs text-gray-400">{{ $card['data']->code }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $card['data']->completed_at?->translatedFormat('d F Y, H:i') }}
                                    @if ($card['data']->period)
                                        &middot; {{ $card['data']->period->name }}
                                    @endif
                                </p>

                                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ $card['data']->recommendedProgram?->full_name ?? '-' }}
                                </h3>

                                <dl class="mt-4 space-y-2 border-t border-gray-200 pt-3 text-sm dark:border-gray-700">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Kode Holland</dt>
                                        <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ $card['data']->holland_code ?? '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Nilai kecocokan</dt>
                                        <dd class="font-semibold tabular-nums text-gray-800 dark:text-gray-200">
                                            {{ number_format($card['data']->recommended_k_normal ?? 0, 2) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Pilihan pertama</dt>
                                        <dd class="text-gray-800 dark:text-gray-200">{{ $card['data']->primaryProgram?->code ?? '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        @endforeach
                    </section>

                    {{-- Apakah rekomendasinya berpindah --}}
                    @if ($left->recommended_program_id === $right->recommended_program_id)
                        <x-alert type="success">
                            Rekomendasi Anda <strong>tetap sama</strong>:
                            <strong>{{ $right->recommendedProgram?->full_name }}</strong>.
                            Konsistensi ini memperkuat keyakinan bahwa prodi tersebut memang sesuai dengan profil Anda.
                        </x-alert>
                    @else
                        <x-alert type="warning">
                            Rekomendasi Anda <strong>berpindah</strong> dari
                            <strong>{{ $left->recommendedProgram?->full_name ?? '-' }}</strong> menjadi
                            <strong>{{ $right->recommendedProgram?->full_name ?? '-' }}</strong>.
                            Periksa tabel di bawah untuk melihat perubahan mana yang menyebabkannya.
                        </x-alert>
                    @endif

                    {{-- Pergeseran profil RIASEC --}}
                    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pergeseran Profil Minat (RIASEC)</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Batang atas adalah tes sebelumnya, batang bawah tes pembanding.
                        </p>

                        <div class="mt-5 space-y-4">
                            @foreach ($riasecDiff as $row)
                                <div>
                                    <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ $row['label'] }}</span>
                                        <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ number_format($row['left'], 2) }}% &rarr; {{ number_format($row['right'], 2) }}%
                                            <span class="ms-2 font-semibold {{ $row['delta'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($row['delta'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400') }}">
                                                {{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 2) }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="mt-1.5 space-y-1">
                                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-full rounded-full opacity-40" style="width: {{ $row['left'] }}%; background-color: {{ $row['color'] }}"></div>
                                        </div>
                                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-full rounded-full" style="width: {{ $row['right'] }}%; background-color: {{ $row['color'] }}"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- Pergeseran nilai rapor --}}
                    <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Perubahan Nilai Rapor</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-3">Mata Pelajaran</th>
                                        <th class="px-6 py-3 text-right">Sebelumnya</th>
                                        <th class="px-6 py-3 text-right">Pembanding</th>
                                        <th class="px-6 py-3 text-right">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($subjectDiff as $row)
                                        <tr class="text-gray-700 dark:text-gray-300">
                                            <td class="px-6 py-3">{{ $row['label'] }}</td>
                                            <td class="px-6 py-3 text-right tabular-nums">{{ number_format($row['left'], 2) }}</td>
                                            <td class="px-6 py-3 text-right tabular-nums">{{ number_format($row['right'], 2) }}</td>
                                            <td class="px-6 py-3 text-right font-semibold tabular-nums {{ $row['delta'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($row['delta'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400') }}">
                                                {{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                {{-- Riwayat seluruh sesi --}}
                <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Riwayat Seluruh Tes</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Terurut dari yang paling lama, sehingga arah perubahannya terlihat.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3">Kode</th>
                                    <th class="px-6 py-3">Holland</th>
                                    <th class="px-6 py-3">Rekomendasi</th>
                                    <th class="px-6 py-3 text-right">Nilai</th>
                                    <th class="px-6 py-3">Pilihan 1</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($timeline as $session)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-6 py-3">{{ $session->completed_at?->translatedFormat('d M Y') }}</td>
                                        <td class="whitespace-nowrap px-6 py-3 font-mono text-xs">{{ $session->code }}</td>
                                        <td class="px-6 py-3 font-semibold">{{ $session->holland_code ?? '-' }}</td>
                                        <td class="px-6 py-3">{{ $session->recommendedProgram?->full_name ?? '-' }}</td>
                                        <td class="px-6 py-3 text-right tabular-nums">{{ number_format($session->recommended_k_normal ?? 0, 2) }}</td>
                                        <td class="px-6 py-3">
                                            @if ($session->matches_preference)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Sesuai</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Berbeda</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right">
                                            <a href="{{ route('assessments.result', $session) }}"
                                               class="font-medium text-brand-600 hover:underline dark:text-brand-400">Lihat</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
