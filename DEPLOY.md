# Panduan Deploy (spkpoliwangi.my.id)

Hosting: JagoanHosting (cPanel). Deploy dilakukan **manual**: push dari lokal, lalu jalankan `./deploy.sh` di server.

## Struktur di server

```
/home/spkpoliw/
├── spk-prod-poliwangi-olsen/   ← clone git projek (vendor, .env, storage ada di sini)
├── public_html/                ← isi public/ disalin ke sini (document root domain)
└── composer.phar               ← tidak bisa dipakai di server (eval diblokir hosting)
```

- `public_html/index.php` milik server (path menunjuk ke `../spk-prod-poliwangi-olsen/`). **Jangan ditimpa.**
- `.env` hanya ada di server. Tidak di-commit.

## Alur tiap ada perubahan

### 1. Di lokal

```bash
npm run build        # WAJIB jika ada perubahan tampilan/CSS/JS
git add -A
git commit -m "pesan perubahan"
git push
```

`git push` hanya mengirim ke GitHub. Situs online **belum berubah** sampai langkah 2.

### 2. Di server (terminal cPanel)

```bash
cd ~/spk-prod-poliwangi-olsen && ./deploy.sh
```

Harus berakhir dengan `Deploy selesai`. Skrip ini: `git pull`, `php artisan migrate --force`, `php artisan optimize:clear`, lalu menyalin `public/` ke `public_html/` (tanpa `index.php` dan `storage`).

### 3. Cek

Buka situs dengan **Ctrl + F5**.

## Hal yang sering terlewat

| Situasi | Yang harus dilakukan |
|---|---|
| Ubah tampilan/CSS/JS | `npm run build` sebelum commit. Server tidak punya Node, memakai `public/build` dari git |
| Tambah/ubah paket Composer | `vendor/` tidak ikut git dan Composer tidak jalan di server. Upload ulang `vendor` (zip, lalu extract di File Manager) |
| Ubah `.env` di server | `php artisan optimize:clear` |
| File di server bentrok saat `git pull` ("untracked files would be overwritten") | Hapus file yang disebut, lalu ulangi `./deploy.sh` |
| Terminal cPanel putus | Klik Reconnect, lalu `cd ~/spk-prod-poliwangi-olsen` |

## Aturan keamanan

- Produksi: `APP_ENV=production` dan `APP_DEBUG=false` di `.env` server.
- Repo GitHub **publik**. Jangan commit `.env`, password, token, atau folder `documentation/` yang berisi catatan internal.
- Jangan pakai `migrate:fresh` di server setelah ada data asli. Gunakan `migrate --force`.
- Selalu akses lewat `https://spkpoliwangi.my.id`.

## Jika ada masalah

- Error 500 atau halaman kosong: `tail -30 ~/spk-prod-poliwangi-olsen/storage/logs/laravel.log`
- CSS/JS tidak termuat: pastikan `public_html/build/` ada, lalu jalankan ulang `./deploy.sh`
- Tabel/kolom tidak ada: `php artisan migrate --force`
