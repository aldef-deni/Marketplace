<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Memeriksa kesiapan Google Sign-In tanpa perlu membuka peramban.
 *
 * Perintah ini menyusun URL otorisasi yang persis sama dengan yang dipakai
 * aplikasi, lalu menanyakannya ke Google. Jawaban Google sudah cukup untuk
 * memastikan Client ID dikenali dan redirect URI-nya terdaftar — dua hal yang
 * paling sering salah dan baru ketahuan setelah pengguna menekan tombolnya.
 */
class CekGoogleSso extends Command
{
    protected $signature = 'google:cek {--tanpa-jaringan : Hanya periksa konfigurasi, jangan hubungi Google}';

    protected $description = 'Periksa konfigurasi dan kesiapan Masuk dengan Google';

    private const URL_OTORISASI = 'https://accounts.google.com/o/oauth2/v2/auth';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <options=bold>Pemeriksaan Masuk dengan Google</>');
        $this->newLine();

        $clientId = (string) config('services.google.client_id');
        $secret = (string) config('services.google.client_secret');
        $redirect = (string) config('services.google.redirect');
        $appUrl = rtrim((string) config('app.url'), '/');

        if (! $this->periksaKonfigurasi($clientId, $secret, $redirect, $appUrl)) {
            $this->newLine();
            $this->components->error('Konfigurasi belum lengkap. Perbaiki dulu poin bertanda x di atas.');
            $this->petunjukEnv();

            return self::FAILURE;
        }

        if ($this->option('tanpa-jaringan')) {
            $this->newLine();
            $this->components->info('Konfigurasi terlihat benar. Pemeriksaan ke Google dilewati.');

            return self::SUCCESS;
        }

        return $this->tanyakanKeGoogle($clientId, $redirect);
    }

    private function periksaKonfigurasi(string $clientId, string $secret, string $redirect, string $appUrl): bool
    {
        $lolos = true;

        if ($clientId === '') {
            $this->baris(false, 'GOOGLE_CLIENT_ID', 'belum diisi');
            $lolos = false;
        } elseif (! str_ends_with($clientId, '.apps.googleusercontent.com')) {
            $this->baris(false, 'GOOGLE_CLIENT_ID', 'formatnya tidak lazim — biasanya berakhiran .apps.googleusercontent.com');
            $lolos = false;
        } else {
            $this->baris(true, 'GOOGLE_CLIENT_ID', Str::limit($clientId, 24).' ('.strlen($clientId).' karakter)');
        }

        if ($secret === '') {
            $this->baris(false, 'GOOGLE_CLIENT_SECRET', 'belum diisi');
            $lolos = false;
        } else {
            $this->baris(true, 'GOOGLE_CLIENT_SECRET', Str::mask($secret, '*', 6).' ('.strlen($secret).' karakter)');
        }

        if ($redirect === '' || ! str_starts_with($redirect, 'http')) {
            $this->baris(false, 'Redirect URI', 'harus berupa URL lengkap, bukan "'.$redirect.'"');

            return false;
        }

        $this->baris(true, 'Redirect URI', $redirect);

        // Kesalahan paling sering: URI-nya benar, tetapi host atau skemanya
        // berbeda dengan APP_URL sehingga tidak cocok dengan yang didaftarkan.
        if (! str_starts_with($redirect, $appUrl.'/')) {
            $this->baris(false, 'Kecocokan APP_URL', 'redirect URI tidak berawalan APP_URL ('.$appUrl.')');
            $lolos = false;
        } else {
            $this->baris(true, 'Kecocokan APP_URL', $appUrl);
        }

        if (! str_ends_with($redirect, '/auth/google/callback')) {
            $this->baris(false, 'Jalur callback', 'harus berakhir dengan /auth/google/callback');
            $lolos = false;
        } else {
            $this->baris(true, 'Jalur callback', '/auth/google/callback');
        }

        if (app()->environment('production') && ! str_starts_with($redirect, 'https://')) {
            $this->baris(false, 'HTTPS', 'di produksi Google menolak redirect URI non-HTTPS');
            $lolos = false;
        }

        return $lolos;
    }

    private function tanyakanKeGoogle(string $clientId, string $redirect): int
    {
        $url = self::URL_OTORISASI.'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => Str::random(40),
        ]);

        $this->newLine();
        $this->line('  Menanyakan ke Google…');

        try {
            $jawaban = Http::withoutRedirecting()->timeout(15)->get($url);
        } catch (Throwable $e) {
            return $this->laporGagalKoneksi($e);
        }

        $this->newLine();

        /*
         | Google tidak menaruh galat di badan respons, melainkan mengalihkan
         | ke halaman galat dengan pesan terbungkus pada parameter authError.
         | Membaca badan saja akan keliru menyimpulkan "berhasil", sebab yang
         | terkirim hanyalah halaman "Moved Temporarily".
         */
        $tujuan = $jawaban->header('Location');

        if ($alasan = $this->bacaPesanGalat($tujuan, $jawaban->body())) {
            return $this->laporPenolakan($alasan, $redirect);
        }

        // Dialihkan ke alur masuk Google berarti Client ID dikenali dan
        // redirect URI-nya diterima.
        $diterima = ($tujuan && Str::contains($tujuan, 'accounts.google.com') && ! Str::contains($tujuan, 'error'))
            || $jawaban->successful();

        if ($diterima) {
            $this->components->info('Google menerima Client ID dan redirect URI ini.');
            $this->newLine();
            $this->line('  Konfigurasi sudah benar. Langkah terakhir hanya bisa diuji lewat peramban:');
            $this->line('  buka <options=bold>'.url('/login').'</> lalu tekan "Masuk dengan Google".');
            $this->newLine();
            $this->line('  <fg=gray>Catatan: Client Secret belum ikut teruji di sini, karena secret baru</>');
            $this->line('  <fg=gray>dipakai saat menukar kode otorisasi setelah pengguna menyetujui.</>');

            return self::SUCCESS;
        }

        $this->components->warn('Jawaban Google tidak dikenali (HTTP '.$jawaban->status().').');
        $this->line('  Buka URL berikut di peramban untuk melihat pesan aslinya:');
        $this->newLine();
        $this->line('  '.$url);

        return self::FAILURE;
    }

    /**
     * Ambil pesan galat Google dari URL pengalihan atau badan respons.
     *
     * Nilai authError berupa protobuf ber-base64url; teks yang terbaca manusia
     * diambil dengan menyaring byte non-cetak.
     */
    private function bacaPesanGalat(?string $tujuan, string $badan = ''): ?string
    {
        if ($tujuan !== null && preg_match('/[?&]authError=([^&]+)/', $tujuan, $cocok)) {
            $sandi = base64_decode(strtr(urldecode($cocok[1]), '-_', '+/'), false);

            if ($sandi !== false) {
                $teks = trim((string) preg_replace('/[^\x20-\x7E]+/', ' ', $sandi));

                if ($teks !== '') {
                    return $teks;
                }
            }
        }

        foreach (['redirect_uri_mismatch', 'invalid_client', 'access_blocked', 'admin_policy_enforced'] as $kata) {
            if (Str::contains((string) $tujuan, $kata, ignoreCase: true)
                || Str::contains($badan, $kata, ignoreCase: true)) {
                return $kata;
            }
        }

        return null;
    }

    private function laporPenolakan(string $alasan, string $redirect): int
    {
        if (Str::contains($alasan, 'redirect_uri_mismatch', ignoreCase: true)) {
            $this->components->error('Google menolak: redirect_uri_mismatch');
            $this->line('  Redirect URI ini belum terdaftar pada OAuth client tersebut:');
            $this->line('  <options=bold>'.$redirect.'</>');
            $this->newLine();
            $this->line('  Tambahkan persis seperti itu di Google Cloud Console →');
            $this->line('  APIs & Services → Credentials → OAuth client → Authorized redirect URIs.');
            $this->line('  <fg=gray>Perhatikan http vs https, dan jangan ada garis miring di akhir.</>');

            return self::FAILURE;
        }

        if (Str::contains($alasan, ['invalid_client', 'was not found'], ignoreCase: true)) {
            $this->components->error('Google menolak: Client ID tidak dikenali');
            $this->line('  <fg=gray>'.$alasan.'</>');
            $this->newLine();
            $this->line('  Salin ulang GOOGLE_CLIENT_ID secara utuh dan pastikan berasal dari');
            $this->line('  project Google Cloud yang benar, lalu php artisan config:clear.');

            return self::FAILURE;
        }

        if (Str::contains($alasan, ['access_blocked', 'admin_policy'], ignoreCase: true)) {
            $this->components->warn('Client ID dan redirect URI benar, tetapi aplikasinya diblokir.');
            $this->line('  <fg=gray>'.$alasan.'</>');
            $this->newLine();
            $this->line('  OAuth consent screen kemungkinan masih berstatus Testing.');
            $this->line('  Tekan "Publish app", atau tambahkan email penguji sebagai Test user.');

            return self::FAILURE;
        }

        $this->components->error('Google menolak permintaan ini.');
        $this->line('  <fg=gray>'.$alasan.'</>');

        return self::FAILURE;
    }

    private function laporGagalKoneksi(Throwable $e): int
    {
        $pesan = $e->getMessage();
        $this->newLine();

        // Sertifikat CA yang belum dipasang mudah tertukar dengan masalah
        // jaringan, padahal perbaikannya sama sekali berbeda.
        if (Str::contains($pesan, ['SSL certificate problem', 'cURL error 60'])) {
            $this->components->error('Sertifikat CA tidak tersedia untuk PHP.');
            $this->line('  Unduh <options=bold>https://curl.se/ca/cacert.pem</>, lalu tunjuk di php.ini:');
            $this->newLine();
            $this->line('  <fg=gray>curl.cainfo = "/jalur/ke/cacert.pem"</>');
            $this->line('  <fg=gray>openssl.cafile = "/jalur/ke/cacert.pem"</>');
            $this->newLine();
            $this->line('  Tanpa ini, penukaran kode ke Google saat login pun akan gagal.');

            return self::FAILURE;
        }

        $this->components->error('Tidak bisa menghubungi Google.');
        $this->line('  <fg=gray>'.$pesan.'</>');
        $this->newLine();
        $this->line('  Periksa apakah server diizinkan membuka koneksi keluar ke internet.');

        return self::FAILURE;
    }

    private function baris(bool $lolos, string $label, string $nilai): void
    {
        $tanda = $lolos ? '<fg=green>v</>' : '<fg=red>x</>';
        $this->line(sprintf('  %s  %-22s <fg=gray>%s</>', $tanda, $label, $nilai));
    }

    private function petunjukEnv(): void
    {
        $this->newLine();
        $this->line('  Isi di <options=bold>.env</> lalu jalankan <options=bold>php artisan config:clear</>:');
        $this->newLine();
        $this->line('  <fg=gray>GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com</>');
        $this->line('  <fg=gray>GOOGLE_CLIENT_SECRET=GOCSPX-xxxxx</>');
        $this->line('  <fg=gray>GOOGLE_REDIRECT_URI='.rtrim((string) config('app.url'), '/').'/auth/google/callback</>');
        $this->newLine();
    }
}
