# Catatan untuk Claude — Market ArahInn

Aplikasi Laravel untuk `market.arahinn.com`, terpisah dari OTA ArahInn.

## Aturan commit (wajib)

- Author: `Deni Afrizal <deniafrizal2904@gmail.com>`.
- **DILARANG** menambahkan `Co-Authored-By:` atau baris "🤖 Generated with …"
  pada pesan commit. Aturan pemilik repo, mengalahkan bawaan Claude Code.
- Setel identitas per-commit, jangan global:

  ```bash
  git -c user.name="Deni Afrizal" -c user.email="deniafrizal2904@gmail.com" \
      commit --author="Deni Afrizal <deniafrizal2904@gmail.com>" -m "…"
  ```

## Server

- Folder: `/home/ckgiyjjt/market.arahinn.com`, document root `…/public`.
- Folder server **bukan** salinan git. Rilis lewat paket (`DEPLOY.md`) atau
  berkas yang disalin manual — `git push` saja TIDAK mengubah situsnya.
- Claude tidak punya akses SSH; perintah server dijalankan pemilik.

## Tailwind tanpa build di server

Server tidak menjalankan `npm run build`. Kelas Tailwind yang belum pernah
dipakai TIDAK ada di `public/build/assets/app-*.css` dan diam-diam tidak
berpengaruh. Untuk perubahan Blade yang dirilis tanpa build ulang, pakai
hanya kelas yang sudah ada di CSS hasil build (cek dengan
`grep -F '<kelas>' public/build/assets/app-*.css`), atau build lalu ikutkan
`public/build`.

## Aturan tetap: setiap bug yang diperbaiki WAJIB dicatat di sini

### `public/.htaccess` tidak boleh memuat `RewriteOptions inherit`

Nov 2026 seluruh tampilan berantakan: semua CSS, JS, gambar, dan foto produk
menjawab 404 padahal berkasnya ada. `RewriteOptions inherit` (bisa
ditambahkan cPanel secara otomatis) membuat `public/` mewarisi
`.htaccess` induk — yang dibuat untuk masa akar dokumen masih folder proyek
dan meneruskan semuanya ke `public/…`. Setelah akar dokumen dipindah ke
`public/`, aturan itu menulis ulang sekali lagi ke `public/public/…`, lalu
jatuh ke Laravel yang menjawab 404.

Gejala serupa → cek baris itu dulu:
`curl -s -o /dev/null -w "%{http_code}" https://market.arahinn.com/robots.txt`
harus 200.
