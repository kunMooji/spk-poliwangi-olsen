{{-- Layar pemuatan awal: menutupi konten sampai window 'load' selesai (lihat
     initPageLoader di app.js), supaya pengguna tidak melihat FOUC/lompatan
     tata letak saat font & gambar masih dimuat. Cincin pulsa di belakang logo
     memberi kesan "sistem sedang bekerja" tanpa perlu teks tambahan. --}}
<div id="page-loader" class="page-loader" role="status" aria-live="polite" aria-label="Memuat halaman">
    <div class="page-loader-rings">
        <span class="page-loader-ring"></span>
        <span class="page-loader-ring"></span>
        <span class="page-loader-ring"></span>
        <img src="{{ asset('images/poliwangi_logo.png') }}" alt="" class="page-loader-logo">
    </div>
</div>
