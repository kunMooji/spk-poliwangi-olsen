<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Pernyataan Kuesioner RIASEC</h2></x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-4 px-5 sm:px-8 lg:px-10 xl:px-12"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table', dialog: @js($errors->any() ? old('_dialog') : null) }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))"
             x-effect="document.documentElement.style.overflow = dialog ? 'hidden' : ''; document.body.style.overflow = dialog ? 'hidden' : ''">
            <x-flash />

            <x-admin-panel-hero eyebrow="Instrumen asesmen" title="Pernyataan Kuesioner RIASEC" description="Kelola butir pernyataan untuk mengukur enam dimensi kepribadian RIASEC.">
                <x-slot:action>
                    <button type="button" @click="dialog = 'create'" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-950/30 transition hover:-translate-y-0.5 hover:bg-brand-400"><x-heroicon-o-plus class="h-4 w-4" /> Tambah Pernyataan</button>
                </x-slot:action>
                <x-slot:content>
                <div class="space-y-4">

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($labels as $code => $label)
                    <a href="{{ route('admin.questions.index', ['dimension' => $code]) }}"
                       class="rounded-xl bg-white p-4 shadow-sm transition hover:ring-2 hover:ring-brand-500 dark:bg-gray-800 {{ request('dimension') === $code ? 'ring-2 ring-brand-500' : '' }}">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $counts[$code] ?? 0 }}
                            <span class="text-xs font-normal text-gray-400">butir aktif</span>
                        </p>
                    </a>
                @endforeach
            </div>

            @php($perDimension = collect(array_keys($labels))->map(fn ($code) => $counts[$code] ?? 0))

            @if ($perDimension->unique()->count() > 1)
                <x-alert type="warning">
                    Jumlah butir aktif antar dimensi belum seimbang. Persentase RIASEC dihitung relatif terhadap
                    jumlah butir per dimensi, namun jumlah yang setara membuat perbandingan antar dimensi lebih adil.
                </x-alert>
            @endif

            @if (request('dimension'))
                <a href="{{ route('admin.questions.index') }}" class="inline-block text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                    &larr; Tampilkan semua dimensi
                </a>
            @endif

            @if (! $questions->isEmpty())
                <div class="flex justify-end">
                    <x-list-view-toggle />
                </div>
            @endif

            <div x-show="view === 'table'" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10">
                <div class="flex items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-6">
                    <div>
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-200">Daftar instrumen</p>
                        <h2 class="mt-1 text-base font-bold text-ink-950 dark:text-white">Pernyataan RIASEC</h2>
                    </div>
                    @if (! $questions->isEmpty())
                        <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700 dark:bg-brand-400/15 dark:text-brand-200">{{ $questions->total() }} butir</span>
                    @endif
                </div>
                @if ($questions->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">Belum ada pernyataan pada dimensi ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-brand-100 text-sm dark:divide-white/10">
                            <thead class="bg-brand-50 text-left text-xs uppercase tracking-wide text-ink-500 dark:bg-black/15 dark:text-porcelain-200/55">
                                <tr>
                                    <th class="px-6 py-3">Urutan</th>
                                    <th class="px-6 py-3">Pernyataan</th>
                                    <th class="px-6 py-3">Dimensi</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-100 dark:divide-white/10">
                                @foreach ($questions as $question)
                                    <tr class="text-ink-700 transition hover:bg-brand-50/70 dark:text-porcelain-100/80 dark:hover:bg-white/[0.04]">
                                        <td class="whitespace-nowrap px-6 py-4">{{ $question->sort_order }}</td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ $question->statement }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                                                  style="background-color: {{ \App\Support\Riasec::color($question->dimension) }}">
                                                {{ $question->dimension }}
                                            </span>
                                            <span class="ms-1 text-xs text-gray-500 dark:text-gray-400">{{ $question->dimension_name }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($question->is_active)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                            @else
                                                <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-1">
                                                <x-icon-button @click="dialog = 'edit-{{ $question->id }}'" color="brand" title="Ubah">
                                                    <x-icon.pencil />
                                                </x-icon-button>

                                                <form action="{{ route('admin.questions.destroy', $question) }}" method="POST"
                                                      onsubmit="return confirm('Hapus pernyataan ini?')">
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

                    <div class="border-t border-brand-100 px-6 py-4 dark:border-white/10">
                        {{ $questions->links() }}
                    </div>
                @endif
            </div>

            @if (! $questions->isEmpty())
                <div x-show="view === 'card'" x-cloak>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($questions as $question)
                            @php($qColor = \App\Support\Riasec::color($question->dimension))
                            <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                                <div class="relative overflow-hidden p-5 text-white"
                                     style="background-color: {{ $qColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                    <span class="pointer-events-none absolute -right-2 -top-8 select-none text-8xl font-black leading-none text-white/10">
                                        {{ $question->dimension }}
                                    </span>

                                    <div class="relative flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 17.25h.007v.008H12v-.008z" />
                                            </svg>
                                            {{ $question->dimension }} &middot; {{ $question->dimension_name }}
                                        </span>
                                        <span class="whitespace-nowrap rounded-full bg-white/20 px-2 py-1 text-[11px] font-medium backdrop-blur-sm">
                                            Urutan {{ $question->sort_order }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col p-5">
                                    <p class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $question->statement }}</p>

                                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
                                        @if ($question->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                        @endif

                                        <div class="inline-flex items-center gap-1">
                                            <x-icon-button @click="dialog = 'edit-{{ $question->id }}'" color="brand" title="Ubah">
                                                <x-icon.pencil />
                                            </x-icon-button>

                                            <form action="{{ route('admin.questions.destroy', $question) }}" method="POST"
                                                  onsubmit="return confirm('Hapus pernyataan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-icon-button type="submit" color="rose" title="Hapus">
                                                    <x-icon.trash />
                                                </x-icon-button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $questions->links() }}
                    </div>
                </div>
            @endif
                </div>
                </x-slot:content>
            </x-admin-panel-hero>

            <template x-teleport="body">
            <div x-show="dialog === 'create'" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-ink-950/55 p-4 backdrop-blur-sm" @keydown.escape.window="dialog = null" role="dialog" aria-modal="true" aria-labelledby="create-question-title">
                <div @click.outside="dialog = null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" class="flex h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-ink-900">
                    <div class="flex shrink-0 items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-200">Instrumen asesmen</p><h2 id="create-question-title" class="mt-1 text-lg font-bold text-ink-950 dark:text-white">Tambah Pernyataan RIASEC</h2></div><button type="button" @click="dialog = null" class="rounded-lg p-2 text-ink-400 transition hover:bg-brand-50 hover:text-ink-900 dark:text-porcelain-200/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Tutup dialog"><x-heroicon-o-x-mark class="h-5 w-5" /></button></div>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-7" data-lenis-prevent><form method="POST" action="{{ route('admin.questions.store') }}">@csrf @include('admin.riasec-questions.form', ['isModal' => true, 'dialogKey' => 'create'])</form></div>
                </div>
            </div>
            </template>

            @foreach ($questions as $editQuestion)
                <template x-teleport="body">
                <div x-show="dialog === 'edit-{{ $editQuestion->id }}'" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-ink-950/55 p-4 backdrop-blur-sm" @keydown.escape.window="dialog = null" role="dialog" aria-modal="true" aria-labelledby="edit-question-title-{{ $editQuestion->id }}">
                    <div @click.outside="dialog = null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" class="flex h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-ink-900">
                        <div class="flex shrink-0 items-center justify-between border-b border-brand-100 px-5 py-4 dark:border-white/10 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-200">Instrumen asesmen</p><h2 id="edit-question-title-{{ $editQuestion->id }}" class="mt-1 text-lg font-bold text-ink-950 dark:text-white">Ubah Pernyataan RIASEC</h2></div><button type="button" @click="dialog = null" class="rounded-lg p-2 text-ink-400 transition hover:bg-brand-50 hover:text-ink-900 dark:text-porcelain-200/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Tutup dialog"><x-heroicon-o-x-mark class="h-5 w-5" /></button></div>
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-7" data-lenis-prevent><form method="POST" action="{{ route('admin.questions.update', $editQuestion) }}">@csrf @method('PUT') @include('admin.riasec-questions.form', ['question' => $editQuestion, 'isModal' => true, 'dialogKey' => 'edit-'.$editQuestion->id])</form></div>
                    </div>
                </div>
                </template>
            @endforeach
        </div>
    </div>
</x-app-layout>
