<?php

namespace App\Console\Commands;

use App\Models\Pembayaran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Memeriksa apakah hasil deploy benar-benar lengkap.
 *
 * "Nothing to migrate" bisa berarti dua hal yang berlawanan: migrasinya memang
 * sudah pernah dijalankan, atau berkasnya tidak ikut terekstrak sehingga
 * Laravel tidak melihat apa pun untuk dijalankan. Perintah ini memeriksa
 * keadaan sebenarnya di basis data dan berkas, bukan sekadar pesan di layar.
 */
class CekSistem extends Command
{
    protected $signature = 'sistem:cek';

    protected $description = 'Periksa kelengkapan hasil deploy: skema, berkas, dan konfigurasi';

    private int $gagal = 0;

    private int $peringatan = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <options=bold>Pemeriksaan Kondisi Market ArahInn</>');

        $this->bagian('Basis data');
        $this->periksaKoneksi();
        $this->periksaTabel();
        $this->periksaKolom();
        $this->periksaMigrasi();

        $this->bagian('Berkas & aset');
        $this->periksaAset();
        $this->periksaJalurBukti();
        $this->periksaIzinTulis();

        $this->bagian('Konfigurasi');
        $this->periksaKonfigurasi();

        $this->newLine();

        if ($this->gagal > 0) {
            $this->components->error("{$this->gagal} pemeriksaan gagal. Perbaiki poin bertanda x di atas.");

            return self::FAILURE;
        }

        if ($this->peringatan > 0) {
            $this->components->warn("Semua pemeriksaan penting lolos, {$this->peringatan} hal perlu diperhatikan.");

            return self::SUCCESS;
        }

        $this->components->info('Semua pemeriksaan lolos. Hasil deploy lengkap.');

        return self::SUCCESS;
    }

    private function periksaKoneksi(): void
    {
        try {
            DB::connection()->getPdo();
            $this->ok('Koneksi basis data', config('database.connections.'.config('database.default').'.database'));
        } catch (Throwable $e) {
            $this->salah('Koneksi basis data', Str::limit($e->getMessage(), 70));
        }
    }

    private function periksaTabel(): void
    {
        $wajib = [
            'users', 'kategoris', 'produks', 'keranjangs', 'alamats',
            'pesanans', 'pesanan_items', 'metode_pembayarans', 'pembayarans',
            'pengirimans', 'notifications', 'sessions',
        ];

        $hilang = array_values(array_filter($wajib, fn ($t) => ! Schema::hasTable($t)));

        if ($hilang === []) {
            $this->ok('Tabel', count($wajib).' tabel lengkap');

            return;
        }

        $this->salah('Tabel hilang', implode(', ', $hilang));
        $this->petunjuk('Berkas migrasinya kemungkinan belum terekstrak. Periksa isi database/migrations lalu ulangi migrate.');
    }

    private function periksaKolom(): void
    {
        // Kolom yang ditambahkan rilis-rilis terakhir; paling sering tertinggal
        // saat berkas migrasi tidak ikut terunggah.
        $wajib = [
            'users.google_id' => 'Masuk dengan Google',
            'users.avatar' => 'Foto profil',
        ];

        foreach ($wajib as $jalur => $fitur) {
            [$tabel, $kolom] = explode('.', $jalur);

            if (! Schema::hasTable($tabel)) {
                continue;
            }

            Schema::hasColumn($tabel, $kolom)
                ? $this->ok('Kolom '.$jalur, $fitur)
                : $this->salah('Kolom '.$jalur.' hilang', $fitur.' tidak akan berfungsi');
        }
    }

    private function periksaMigrasi(): void
    {
        $berkas = glob(database_path('migrations/*.php')) ?: [];

        if ($berkas === []) {
            $this->salah('Berkas migrasi', 'folder database/migrations kosong');

            return;
        }

        try {
            $sudah = DB::table('migrations')->pluck('migration')->all();
        } catch (Throwable) {
            $this->salah('Tabel migrations', 'belum ada — jalankan php artisan migrate --force');

            return;
        }

        $tertunda = array_values(array_filter(
            array_map(fn ($b) => basename($b, '.php'), $berkas),
            fn ($n) => ! in_array($n, $sudah, true),
        ));

        $tertunda === []
            ? $this->ok('Migrasi', count($berkas).' berkas, semuanya sudah dijalankan')
            : $this->salah('Migrasi tertunda', implode(', ', $tertunda));
    }

    private function periksaAset(): void
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            $this->salah('Aset frontend', 'public/build/manifest.json tidak ada — halaman akan tampil tanpa CSS');

            return;
        }

        $isi = json_decode((string) file_get_contents($manifest), true) ?: [];
        $hilang = [];

        foreach ($isi as $entri) {
            if (! empty($entri['file']) && ! is_file(public_path('build/'.$entri['file']))) {
                $hilang[] = $entri['file'];
            }
        }

        $hilang === []
            ? $this->ok('Aset frontend', count($isi).' entri manifest cocok dengan berkasnya')
            : $this->salah('Aset frontend', 'disebut manifest tapi tidak ada: '.implode(', ', $hilang));
    }

    private function periksaJalurBukti(): void
    {
        if (! Schema::hasTable('pembayarans')) {
            return;
        }

        $salahJalur = Pembayaran::whereNotNull('bukti')
            ->where('bukti', 'not like', 'uploads/%')
            ->where('bukti', 'not like', 'http%')
            ->count();

        $salahJalur === 0
            ? $this->ok('Jalur bukti pembayaran', 'seluruhnya berawalan uploads/')
            : $this->salah('Jalur bukti pembayaran', "{$salahJalur} baris masih jalur lama, gambarnya akan 404");
    }

    private function periksaIzinTulis(): void
    {
        foreach (['storage/framework', 'storage/logs', 'bootstrap/cache', 'public/uploads'] as $folder) {
            $jalur = base_path($folder);

            if (! is_dir($jalur)) {
                $this->peringatan('Folder '.$folder, 'tidak ada');

                continue;
            }

            is_writable($jalur)
                ? $this->ok('Dapat ditulis', $folder)
                : $this->salah('Tidak dapat ditulis', $folder.' — jalankan chmod -R 775 pada folder ini');
        }
    }

    private function periksaKonfigurasi(): void
    {
        $env = (string) config('app.env');
        $url = (string) config('app.url');

        $env === 'production'
            ? $this->ok('APP_ENV', $env)
            : $this->peringatan('APP_ENV', $env.' — di server seharusnya production');

        // Di komputer pengembang APP_DEBUG=true justru diperlukan; yang
        // berbahaya hanyalah bila menyala di lingkungan produksi.
        if (! config('app.debug')) {
            $this->ok('APP_DEBUG', 'false');
        } elseif ($env === 'production') {
            $this->salah('APP_DEBUG', 'masih true di produksi — halaman galat akan membocorkan isi .env');
        } else {
            $this->ok('APP_DEBUG', 'true, wajar di lingkungan '.$env);
        }

        str_starts_with($url, 'https://')
            ? $this->ok('APP_URL', $url)
            : $this->peringatan('APP_URL', $url.' — situs dilayani lewat HTTPS');

        googleAktif()
            ? $this->ok('Masuk dengan Google', 'kredensial terisi')
            : $this->peringatan('Masuk dengan Google', 'kredensial kosong, tombolnya disembunyikan');
    }

    private function bagian(string $judul): void
    {
        $this->newLine();
        $this->line('  <fg=gray>'.strtoupper($judul).'</>');
    }

    private function ok(string $label, string $nilai): void
    {
        $this->baris('<fg=green>v</>', $label, $nilai);
    }

    private function salah(string $label, string $nilai): void
    {
        $this->gagal++;
        $this->baris('<fg=red>x</>', $label, $nilai);
    }

    private function peringatan(string $label, string $nilai): void
    {
        $this->peringatan++;
        $this->baris('<fg=yellow>!</>', $label, $nilai);
    }

    private function baris(string $tanda, string $label, string $nilai): void
    {
        $this->line(sprintf('  %s  %-26s <fg=gray>%s</>', $tanda, $label, $nilai));
    }

    private function petunjuk(string $teks): void
    {
        $this->line('     <fg=gray>'.$teks.'</>');
    }
}
