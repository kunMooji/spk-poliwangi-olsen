@props([
    // Ekspresi Alpine bernilai 0..100. Dibaca dari $store.wizard karena bilah
    // ini dirender di app bar (layout), di luar cakupan x-data halaman.
    'expression' => '0',
])

{{--
    Bilah kemajuan tes versi HP/tablet: tipis, tanpa teks persen, menempel di
    sisi bawah app bar yang sudah sticky — jadi selalu terlihat saat digulir.
    Di layar lebar (lg) kartu "Progress Assessment" di samping konten yang dipakai.
--}}
<div x-data class="h-1.5 w-full bg-brand-100/70 dark:bg-white/10 lg:hidden"
     role="progressbar" aria-label="Kemajuan tes" aria-valuemin="0" aria-valuemax="100"
     :aria-valuenow="Math.round({{ $expression }})">
    <div class="h-full rounded-r-full bg-brand-600 transition-[width] duration-300 ease-out dark:bg-brand-400"
         :style="`width: ${Math.min(100, Math.max(0, {{ $expression }}))}%`"></div>
</div>
