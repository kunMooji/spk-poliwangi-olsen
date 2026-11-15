# Aturan projek

## Deploy
Panduan lengkap ada di [DEPLOY.md](DEPLOY.md). Ikuti alur itu setiap kali ada perubahan yang akan dipublikasikan:

- Deploy **manual**: `git push` dari lokal, lalu user menjalankan `./deploy.sh` di terminal cPanel. Push tidak otomatis mengubah situs online.
- Jika perubahan menyentuh tampilan, CSS, atau JS: jalankan `npm run build` **sebelum commit**. Server tidak punya Node dan memakai `public/build` dari git.
- Jika menambah atau mengubah paket Composer: ingatkan user bahwa `vendor/` harus diunggah manual (Composer tidak bisa jalan di server karena `eval` diblokir).
- Selalu minta konfirmasi user sebelum `git push`. Repo GitHub **publik**: jangan commit `.env`, password, token, atau folder `documentation/`.
- Setelah push, ingatkan user menjalankan `cd ~/spk-prod-poliwangi-olsen && ./deploy.sh` di server dan cek dengan Ctrl + F5.
- Jangan sarankan `migrate:fresh` di server. Pakai `migrate --force`.
- Produksi harus `APP_DEBUG=false`.
