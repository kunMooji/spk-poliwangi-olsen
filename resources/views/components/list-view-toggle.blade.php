{{--
    Sakelar tampilan tabel/card.

    Dipakai di dalam elemen yang membawa state Alpine `view` ('table'|'card'),
    lihat resources/views/admin/*/index.blade.php — wrapper terluar tiap
    halaman daftar mendeklarasikan:

        x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
        x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))"

    Preferensinya sengaja satu kunci localStorage yang dipakai bersama seluruh
    halaman daftar, supaya sekali dipilih card, seluruh halaman admin lain
    ikut memakainya juga.
--}}
<div class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-800" role="group" aria-label="Pilih tampilan data">
    <button type="button" @click="view = 'table'" title="Tampilan tabel" aria-label="Tampilan tabel"
            :class="view === 'table' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    <button type="button" @click="view = 'card'" title="Tampilan card" aria-label="Tampilan card"
            :class="view === 'card' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="7" height="7" rx="1.5" />
            <rect x="13" y="4" width="7" height="7" rx="1.5" />
            <rect x="4" y="13" width="7" height="7" rx="1.5" />
            <rect x="13" y="13" width="7" height="7" rx="1.5" />
        </svg>
    </button>
</div>
