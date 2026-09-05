# Deploy Market ArahInn ke `market.arahinn.com`

Panduan ini untuk server ArahInn yang **tidak memakai VM/kontainer** — rilis
dilakukan dengan mengunggah berkas lewat cPanel/FTP/SSH.

---

## 1. Membuat Paket Rilis (di komputer lokal)

```bash
# Paket lengkap — untuk pemasangan pertama
php artisan rilis:paket

# Hanya berkas yang berubah — untuk pembaruan berkala
php artisan rilis:paket --sejak=HEAD~1
```

Hasilnya berupa `market-arahinn-<tanggal>.zip` di folder **Downloads**, sudah
berisi aset frontend terkompilasi (`public/build`). Di dalamnya juga ada
`CARA-DEPLOY.txt` yang meringkas langkah-langkah di bawah.

Yang **tidak** ikut dibungkus, dan alasannya:

| Tidak disertakan | Alasan |
|------------------|--------|
| `.env` | Berisi kredensial produksi; jangan pernah ditimpa dari lokal |
| `vendor/` | Dipasang di server dengan `composer install` |
| `node_modules/` | Hanya dibutuhkan saat build, dan build sudah dilakukan di lokal |
| `storage/logs`, `storage/framework` | Berkas kerja milik server |
| `tests/`, `phpunit.xml` | Tidak diperlukan di produksi |

---

## 2. Prasyarat di Server

- PHP **≥ 8.2** dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`
- MySQL / MariaDB
- Composer 2.x
- Sertifikat SSL aktif untuk `market.arahinn.com`

### Composer di server ArahInn

Composer **tidak ada di PATH**. Yang dipakai adalah `composer.phar` di root
aplikasi, jadi seluruh perintah composer ditulis begini:

```bash
cd /home/ckgiyjjt/market.arahinn.com
php composer.phar install --no-dev --optimize-autoloader
```

Bila `composer.phar` belum ada, pasang sekali saja:

```bash
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=. --filename=composer.phar
rm composer-setup.php
```

Berkas itu berada di luar `public/`, jadi tidak dapat diakses dari web.
Periksa juga `php -v` — Composer menyelesaikan dependensi berdasarkan PHP CLI,
yang di cPanel kadang berbeda versi dari PHP web.

> Node.js **tidak** dibutuhkan di server — aset sudah dikompilasi di lokal.

---

## 3. Struktur Folder di Server

Document root subdomain **harus** mengarah ke folder `public/`, bukan ke root
aplikasi. Kalau root aplikasi terekspos, berkas `.env` bisa diunduh publik.

```
/home/arahinn/
├── market-arahinn/            ← root aplikasi (di luar public_html)
│   ├── app/  bootstrap/  config/  database/  resources/  routes/  storage/
│   ├── vendor/
│   ├── public/                ← document root subdomain menunjuk ke sini
│   └── .env
```

Di cPanel: **Domains → Subdomains → `market`**, lalu isi Document Root dengan
`/home/arahinn/market-arahinn/public`.

Bila panel hosting memaksa document root berada di dalam `public_html`, buat
symlink:

```bash
ln -s /home/arahinn/market-arahinn/public /home/arahinn/public_html/market
```

---

## 4. Pemasangan Pertama

```bash
cd /home/arahinn/market-arahinn

# 1. Ekstrak isi ZIP di sini

# 2. Dependensi produksi
composer install --no-dev --optimize-autoloader

# 3. Konfigurasi
cp .env.example .env
php artisan key:generate
```

Sunting `.env` — minimal bagian berikut:

```env
APP_NAME="Market ArahInn"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://market.arahinn.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arahinn_market
DB_USERNAME=arahinn_market
DB_PASSWORD=<kata-sandi-kuat>

SESSION_SECURE_COOKIE=true
LOG_LEVEL=error

MAIL_MAILER=smtp
MAIL_HOST=<smtp-arahinn>
MAIL_PORT=587
MAIL_USERNAME=<akun>
MAIL_PASSWORD=<sandi>
MAIL_FROM_ADDRESS="halo@arahinn.com"
```

> `APP_DEBUG=false` wajib. Bila `true`, halaman galat akan menampilkan isi
> `.env` termasuk kata sandi database kepada siapa pun yang membukanya.

Lanjutkan:

```bash
# 4. Skema database + data awal (kategori, metode bayar, akun contoh)
php artisan migrate --seed --force

# 5. Symlink penyimpanan & izin folder
php artisan storage:link
chmod -R 775 storage bootstrap/cache

# 6. Cache konfigurasi, rute, dan view
php artisan optimize
```

### Segera setelah pemasangan

Ganti kata sandi ketiga akun bawaan (`superadmin@arahinn.com`,
`admin@arahinn.com`, `pengguna@arahinn.com` — semuanya `password`):

```bash
php artisan tinker
>>> App\Models\User::where('email','superadmin@arahinn.com')->update(['password' => bcrypt('sandi-baru-yang-kuat')]);
```

Atau hapus akun contoh yang tidak dipakai lewat **Panel Admin → Pengguna**.

---

## 5. Pembaruan Berikutnya

```bash
cd /home/arahinn/market-arahinn
php artisan down                 # mode perbaikan, opsional

# Ekstrak ZIP perubahan di sini (jangan sertakan .env)

composer install --no-dev --optimize-autoloader   # bila composer.json berubah
php artisan migrate --force
php artisan optimize                              # membangun ulang cache

php artisan up
```

Setelah selesai, pastikan hasilnya benar-benar lengkap:

```bash
php artisan sistem:cek
```

Perintah ini memeriksa keadaan sebenarnya — tabel, kolom, migrasi tertunda,
kecocokan aset dengan manifest, izin tulis, dan konfigurasi — bukan sekadar
pesan yang muncul di layar. Berguna terutama karena `migrate` menjawab
**"Nothing to migrate"** untuk dua keadaan yang berlawanan: migrasinya memang
sudah dijalankan, atau berkasnya tidak ikut terekstrak sehingga Laravel tidak
melihat apa pun untuk dijalankan.

`php artisan optimize` menyegarkan cache config, rute, dan view sekaligus.
Melewatkannya adalah penyebab paling umum "kenapa perubahan saya tidak muncul".

Tiga hal yang terbukti menjatuhkan situs saat rilis Google SSO, semuanya karena
langkah di atas dilewati:

| Gejala | Penyebab |
|---|---|
| `Route [x] not defined` | Cache rute masih versi lama. `config:clear` **tidak** menyentuhnya — perlu `route:clear` atau `optimize` |
| `Class "..." not found` | `composer install` belum dijalankan padahal paketnya berubah |
| `foreach() argument must be of type array` | Cache config dibuat sebelum kunci barunya ada |

Urutannya penting: **ekstrak seluruh berkas dulu, baru `optimize`**. Menjalankan
`optimize` lebih dahulu membuat rute dan config baru tidak ikut terbaca.

Nama berkas aset di `public/build` mengandung hash, jadi peramban pengunjung
otomatis mengambil versi baru tanpa perlu hard refresh.

---

## 6. Konfigurasi Web Server

### Apache (cPanel / shared hosting)

Berkas `public/.htaccess` bawaan Laravel sudah memadai. Tambahkan pengalihan
ke HTTPS di `.htaccess` root subdomain bila panel belum menanganinya:

```apache
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://market.arahinn.com/$1 [R=301,L]
```

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name market.arahinn.com;
    root /home/arahinn/market-arahinn/public;

    index index.php;
    charset utf-8;
    client_max_body_size 8M;          # cukup untuk unggahan bukti bayar

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Berkas ber-hash aman di-cache lama.
    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known).* { deny all; }

    error_page 404 /index.php;
}

server {
    listen 80;
    server_name market.arahinn.com;
    return 301 https://$host$request_uri;
}
```

Aplikasi sudah menyetel `URL::forceScheme('https')` saat `APP_ENV=production`
dan mempercayai header `X-Forwarded-*`, sehingga tautan dan aset tetap terbentuk
sebagai `https://` walau PHP berada di balik proxy.

---

## 7. Masuk dengan Google (SSO)

Tombol &ldquo;Masuk dengan Google&rdquo; hanya muncul bila `GOOGLE_CLIENT_ID` dan
`GOOGLE_CLIENT_SECRET` terisi. Kalau dikosongkan, situs tetap berjalan normal
dengan login email dan kata sandi saja.

### Mengambil kredensial

1. Buka [Google Cloud Console](https://console.cloud.google.com/) → buat project
   (atau pilih yang sudah ada).
2. **APIs & Services → OAuth consent screen**
   - User Type: **External**, lalu **Publish app** agar bisa dipakai siapa saja
     (selama masih *Testing*, hanya akun yang didaftarkan sebagai test user yang bisa masuk)
   - App name: `Market ArahInn`
   - Authorized domain: `arahinn.com`
   - Scope cukup bawaan: `email`, `profile`, `openid`
3. **APIs & Services → Credentials → Create Credentials → OAuth client ID**
   - Application type: **Web application**
   - **Authorized JavaScript origins**
     ```
     https://market.arahinn.com
     ```
   - **Authorized redirect URIs** — harus sama persis, tanpa garis miring di akhir:
     ```
     https://market.arahinn.com/auth/google/callback
     ```
4. Salin **Client ID** dan **Client secret** ke `.env` server:

   ```env
   GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxx
   GOOGLE_REDIRECT_URI=https://market.arahinn.com/auth/google/callback
   ```

5. Terapkan:

   ```bash
   php artisan config:clear
   php artisan optimize
   ```

### Untuk pengembangan di komputer lokal

Tambahkan satu redirect URI lagi pada OAuth client yang sama:

```
http://localhost:8000/auth/google/callback
```

lalu isi `GOOGLE_REDIRECT_URI` di `.env` lokal dengan alamat tersebut.

### Cara kerja penautan akun

- Pencocokan dilakukan berdasarkan `google_id` lebih dulu, baru alamat email.
  Pengguna yang dulu mendaftar dengan kata sandi lalu memakai tombol Google
  akan **masuk ke akun yang sama**, bukan membuat akun kedua.
- Surel dari Google yang belum terverifikasi ditolak. Tanpa pemeriksaan ini,
  seseorang bisa mendaftarkan alamat email orang lain di Google dan memakainya
  untuk mengambil alih akun di sini.
- Akun hasil pendaftaran Google tidak memiliki kata sandi. Pemiliknya bisa
  membuat kata sandi kapan saja lewat **Profil → Buat Kata Sandi**, sehingga
  setelahnya bisa masuk dengan dua cara.

### Memeriksa tanpa membuka peramban

Setelah kredensial diisi, jalankan di server:

```bash
php artisan google:cek
```

Perintah ini memeriksa isian `.env`, mencocokkan redirect URI dengan `APP_URL`,
lalu menanyakan langsung ke Google apakah Client ID dikenali dan redirect
URI-nya terdaftar — jadi kesalahan ketik ketahuan sebelum ada pengguna yang
menekan tombolnya.

Pakai `--tanpa-jaringan` bila hanya ingin memeriksa isian `.env` saja.

> Client Secret belum ikut teruji oleh perintah ini, sebab secret baru dipakai
> saat menukar kode otorisasi setelah pengguna menyetujui di layar Google.
> Uji akhir tetap dengan menekan tombolnya sekali di peramban.

### Bila gagal

| Pesan | Penyebab |
|---|---|
| `redirect_uri_mismatch` | URI di Google Console berbeda dengan `GOOGLE_REDIRECT_URI` — periksa `http` vs `https` dan garis miring di akhir |
| `Access blocked: app not verified` | OAuth consent screen masih berstatus *Testing*; publikasikan, atau tambahkan email penguji |
| Tombol tidak muncul | `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` kosong, atau config cache belum disegarkan |
| `Gagal terhubung ke Google` | Periksa `storage/logs/laravel.log`; biasanya Client Secret salah atau server tidak bisa keluar ke internet |

---

## 8. Tugas Terjadwal (opsional)

Bila nanti dipakai antrean atau pembersihan pesanan kedaluwarsa, tambahkan satu
cron:

```cron
* * * * * cd /home/arahinn/market-arahinn && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Daftar Periksa Sebelum Diumumkan

- [ ] `APP_DEBUG=false` dan `APP_ENV=production`
- [ ] `APP_URL=https://market.arahinn.com`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Document root menunjuk ke `public/`, dan `.env` tidak bisa diakses dari web
      (cek: buka `https://market.arahinn.com/.env` — harus 403/404)
- [ ] Kata sandi akun bawaan sudah diganti
- [ ] Pengguna database bukan `root`
- [ ] `storage/` dan `bootstrap/cache/` dapat ditulis (775)
- [ ] `php artisan storage:link` sudah dijalankan
- [ ] Nomor rekening dan instruksi pembayaran diperbarui lewat
      **Panel Admin → Metode Pembayaran**
- [ ] Kontak di `config/brand.php` (email, WhatsApp, alamat) sudah benar
- [ ] Redirect URI Google cocok dan OAuth consent screen sudah dipublikasikan
- [ ] Uji satu siklus penuh: daftar → belanja → checkout → unggah bukti →
      verifikasi admin → input resi → konfirmasi diterima → cetak invoice

---

## 10. Bila Terjadi Masalah

| Gejala | Penyebab yang paling sering |
|--------|------------------------------|
| Halaman putih / HTTP 500 | Izin `storage/` dan `bootstrap/cache/`; periksa `storage/logs/laravel.log` |
| Tampilan tanpa CSS | `public/build` tidak ikut terunggah, atau document root salah |
| Perubahan tidak muncul | Cache lama — jalankan `php artisan optimize` |
| Aset dimuat sebagai `http://` | `APP_ENV` belum `production`, sehingga HTTPS tidak dipaksa |
| Gambar produk tidak tampil | `php artisan storage:link` belum dijalankan |
| "419 Page Expired" saat login | `SESSION_SECURE_COOKIE=true` tetapi situs diakses via HTTP |
