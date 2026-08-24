<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Tambah Kriteria</h2>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-none px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            <x-admin-panel-hero eyebrow="Parameter keputusan" title="Tambah Kriteria" description="Tambahkan parameter baru dan tentukan bobotnya untuk perhitungan rekomendasi CoCoSo.">
                <x-slot:content>
                    <div class="max-w-4xl rounded-2xl border border-brand-100 bg-white/85 p-5 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-none sm:p-7">
                        <form method="POST" action="{{ route('admin.criteria.store') }}">
                            @csrf
                            @include('admin.criteria.form')
                        </form>
                    </div>
                </x-slot:content>
            </x-admin-panel-hero>
        </div>
    </div>
</x-app-layout>
