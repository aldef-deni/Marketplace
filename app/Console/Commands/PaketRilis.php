<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * Membungkus perubahan aplikasi menjadi satu berkas ZIP di folder Downloads,
 * siap diunggah manual ke market.arahinn.com.
 *
 * Server ArahInn tidak memakai VM/CI, jadi rilis dilakukan dengan mengunggah
 * berkas. Perintah ini menyiapkan isinya supaya tidak ada yang terlewat dan
 * tidak ada yang salah ikut terbawa (kredensial, dependensi, berkas kerja).
 */
class PaketRilis extends Command
{
    protected $signature = 'rilis:paket
        {--sejak= : Hanya sertakan berkas yang berubah sejak ref git ini, misal HEAD~1 atau nama tag}
        {--penuh : Bungkus seluruh aplikasi, bukan hanya perubahan}
        {--tujuan= : Folder tujuan ZIP (baku: folder Downloads pengguna)}
        {--tanpa-build : Lewati "npm run build"; pakai aset yang sudah ada}';

    protected $description = 'Bungkus perubahan menjadi ZIP siap unggah ke market.arahinn.com';

    /**
     * Folder dan berkas yang tidak boleh ikut terkirim ke server.
     *
     * vendor/ dan node_modules/ dipasang di server lewat composer/npm;
     * .env berisi kredensial produksi yang tidak boleh ditimpa dari lokal.
     */
    private const KECUALI_AWALAN = [
        '.git/', 'node_modules/', 'vendor/', 'storage/framework/', 'storage/logs/',
        'tests/', '.github/', '.idea/', '.vscode/',
    ];

    /**
     * Catatan commit yang terakhir dibungkus, dipakai sebagai titik awal paket
     * berikutnya. Tanpa ini satu paket yang terlewat membuat paket sesudahnya
     * kehilangan berkas yang dirujuk kode baru, dan server langsung 500.
     */
    private const BERKAS_PENANDA = 'storage/app/rilis-terakhir.txt';

    private const KECUALI_BERKAS = [
        '.env', '.env.backup', '.env.production', '.phpunit.result.cache',
        self::BERKAS_PENANDA,
        'database/database.sqlite', 'package-lock.json',
        'phpunit.xml', '.editorconfig', '.gitattributes',
    ];

    public function handle(): int
    {
        $akar = base_path();

        if (! $this->option('tanpa-build')) {
            $this->components->task('Membangun aset frontend', function () use ($akar) {
                return Process::path($akar)->timeout(600)->run('npm run build')->successful();
            });
        }

        $sejak = $this->titikAwal($akar);

        $berkas = $sejak
            ? $this->berkasBerubah($akar, $sejak)
            : $this->seluruhBerkas($akar);

        if ($berkas === null) {
            return self::FAILURE;
        }

        if ($berkas === []) {
            $this->components->warn('Tidak ada berkas yang perlu dikirim.');

            return self::SUCCESS;
        }

        $tujuan = $this->folderTujuan();
        $nama = sprintf(
            'market-arahinn-%s%s.zip',
            now()->format('Ymd-Hi'),
            $sejak ? '-perubahan' : '-lengkap',
        );
        $path = $tujuan.DIRECTORY_SEPARATOR.$nama;

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->components->error("Tidak dapat menulis ke {$path}");

            return self::FAILURE;
        }

        foreach ($berkas as $relatif) {
            $zip->addFile($akar.DIRECTORY_SEPARATOR.$relatif, $relatif);
        }

        $zip->addFromString('CARA-DEPLOY.txt', $this->catatanDeploy($berkas, $this->daftarCommit($akar, $sejak)));
        $zip->close();

        $this->newLine();
        $this->components->info('Paket rilis siap.');
        $this->components->twoColumnDetail('Berkas', $path);
        $this->components->twoColumnDetail('Jumlah berkas', (string) count($berkas));
        $this->components->twoColumnDetail('Ukuran', $this->ukuran(filesize($path)));

        if ($sejak) {
            $this->components->twoColumnDetail('Mencakup commit', $this->ringkasanCommit($akar, $sejak));
        }

        $this->catatPenanda($akar);

        $this->newLine();
        $this->line('  Unggah dan ekstrak di root aplikasi pada server, lalu jalankan:');
        $this->line('  <fg=gray>php artisan migrate --force && php artisan optimize</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Titik awal paket: opsi eksplisit, lalu commit yang terakhir dibungkus.
     *
     * Baku bertumpu pada penanda, bukan HEAD~1, supaya paket berikutnya tetap
     * memuat commit yang paketnya terlewat alih-alih melompatinya.
     */
    private function titikAwal(string $akar): ?string
    {
        if ($this->option('penuh')) {
            return null;
        }

        if ($sejak = $this->option('sejak')) {
            return $sejak;
        }

        $penanda = $akar.DIRECTORY_SEPARATOR.self::BERKAS_PENANDA;

        if (! is_file($penanda)) {
            return null;
        }

        $ref = trim((string) file_get_contents($penanda));

        // Penanda yang menunjuk commit tak dikenal (riwayat ditulis ulang,
        // klon baru) diabaikan; paket penuh lebih aman daripada paket bolong.
        if ($ref === '' || ! $this->adaGit($akar)) {
            return null;
        }

        $ada = Process::path($akar)->run(sprintf('git cat-file -e %s^{commit}', escapeshellarg($ref)));

        if (! $ada->successful()) {
            $this->components->warn("Penanda rilis '{$ref}' tidak dikenal; membungkus seluruh aplikasi.");

            return null;
        }

        return $ref;
    }

    private function catatPenanda(string $akar): void
    {
        if (! $this->adaGit($akar)) {
            return;
        }

        $head = trim((string) Process::path($akar)->run('git rev-parse HEAD')->output());

        if ($head === '') {
            return;
        }

        $penanda = $akar.DIRECTORY_SEPARATOR.self::BERKAS_PENANDA;

        @mkdir(dirname($penanda), 0755, true);
        file_put_contents($penanda, $head.PHP_EOL);
    }

    /**
     * Ringkasan commit yang tercakup, agar terlihat bila ada yang terlewat.
     */
    private function ringkasanCommit(string $akar, string $sejak): string
    {
        $keluaran = Process::path($akar)
            ->run(sprintf('git log --oneline %s..HEAD', escapeshellarg($sejak)))
            ->output();

        $baris = preg_split('/\R/', trim($keluaran), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($baris === []) {
            return 'tidak ada commit baru';
        }

        return count($baris).' commit sejak '.Str::substr($sejak, 0, 7);
    }

    /**
     * Seluruh berkas terlacak git, dikurangi yang masuk daftar kecualian.
     */
    private function seluruhBerkas(string $akar): ?array
    {
        $daftar = $this->adaGit($akar)
            ? $this->keluaranGit($akar, 'git ls-files --cached --others --exclude-standard')
            : $this->telusuriFolder($akar);

        if ($daftar === null) {
            return null;
        }

        $gabungan = array_unique([...$daftar, ...($this->telusuriFolder($akar, 'public/build') ?? [])]);
        sort($gabungan);

        return $this->saring($akar, $gabungan);
    }

    /**
     * Berkas yang berubah sejak sebuah ref, ditambah hasil build terbaru —
     * aset punya nama ber-hash sehingga selalu perlu ikut agar manifest cocok.
     */
    private function berkasBerubah(string $akar, string $ref): ?array
    {
        if (! $this->adaGit($akar)) {
            $this->components->error('Opsi --sejak memerlukan repositori git.');

            return null;
        }

        $berubah = $this->keluaranGit($akar, sprintf('git diff --name-only %s HEAD', escapeshellarg($ref)));
        if ($berubah === null) {
            $this->components->error("Ref git '{$ref}' tidak dikenal.");

            return null;
        }

        $belumDicommit = $this->keluaranGit($akar, 'git ls-files --modified --others --exclude-standard') ?? [];

        // git diff mendaftar berkas apa adanya, termasuk yang kini masuk
        // .gitignore. Unggahan pengguna yang baru dilepas dari pelacakan masih
        // ada di disk, jadi tanpa penyaringan ini berkas milik orang lain bisa
        // ikut terbungkus dan terkirim.
        $kandidat = $this->buangYangDiabaikan($akar, array_unique([...$berubah, ...$belumDicommit]));

        // public/build sengaja ditambahkan setelah penyaringan: folder itu
        // memang diabaikan git, tetapi wajib ikut karena server tidak menjalankan npm.
        $gabungan = array_unique([...$kandidat, ...($this->telusuriFolder($akar, 'public/build') ?? [])]);
        sort($gabungan);

        // Berkas yang dihapus tetap muncul di git diff; jangan dicoba dibungkus.
        $ada = array_filter($gabungan, fn ($r) => is_file($akar.DIRECTORY_SEPARATOR.$r));

        return $this->saring($akar, array_values($ada));
    }

    /**
     * Singkirkan berkas yang diabaikan git.
     *
     * check-ignore mengembalikan kode keluar bukan-nol bila tidak ada yang
     * cocok, sehingga hasilnya dibaca langsung dan bukan lewat keluaranGit()
     * yang memperlakukan keadaan itu sebagai kegagalan.
     */
    private function buangYangDiabaikan(string $akar, array $daftar): array
    {
        if ($daftar === []) {
            return [];
        }

        $hasil = Process::path($akar)
            ->input(implode("\n", $daftar))
            ->run('git check-ignore --stdin');

        $diabaikan = array_flip(array_filter(
            array_map('trim', explode("\n", $hasil->output())),
            fn ($baris) => $baris !== '',
        ));

        return array_values(array_filter($daftar, fn ($r) => ! isset($diabaikan[$r])));
    }

    private function saring(string $akar, array $daftar): array
    {
        return array_values(array_filter($daftar, function (string $relatif) use ($akar) {
            if (! is_file($akar.DIRECTORY_SEPARATOR.$relatif)) {
                return false;
            }
            if (in_array($relatif, self::KECUALI_BERKAS, true)) {
                return false;
            }

            return ! Str::startsWith($relatif, self::KECUALI_AWALAN);
        }));
    }

    private function telusuriFolder(string $akar, string $sub = ''): ?array
    {
        $mulai = $sub ? $akar.DIRECTORY_SEPARATOR.$sub : $akar;
        if (! is_dir($mulai)) {
            return [];
        }

        $hasil = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($mulai, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }
            $relatif = str_replace('\\', '/', Str::after($item->getPathname(), $akar.DIRECTORY_SEPARATOR));
            if (! Str::startsWith($relatif, self::KECUALI_AWALAN)) {
                $hasil[] = $relatif;
            }
        }

        sort($hasil);

        return $hasil;
    }

    private function adaGit(string $akar): bool
    {
        return is_dir($akar.DIRECTORY_SEPARATOR.'.git');
    }

    private function keluaranGit(string $akar, string $perintah): ?array
    {
        $hasil = Process::path($akar)->run($perintah);

        if (! $hasil->successful()) {
            return null;
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $hasil->output())),
            fn ($baris) => $baris !== '',
        ));
    }

    private function folderTujuan(): string
    {
        if ($pilihan = $this->option('tujuan')) {
            return rtrim($pilihan, '\\/');
        }

        $rumah = getenv('USERPROFILE') ?: getenv('HOME') ?: base_path();
        $unduhan = $rumah.DIRECTORY_SEPARATOR.'Downloads';

        return is_dir($unduhan) ? $unduhan : $rumah;
    }

    private function ukuran(int $bita): string
    {
        return $bita >= 1_048_576
            ? round($bita / 1_048_576, 1).' MB'
            : round($bita / 1024).' KB';
    }

    /**
     * Daftar commit yang tercakup paket ini, untuk ditulis di catatan deploy.
     */
    private function daftarCommit(string $akar, ?string $sejak): string
    {
        if (! $sejak || ! $this->adaGit($akar)) {
            return '';
        }

        $keluaran = trim((string) Process::path($akar)
            ->run(sprintf('git log --oneline %s..HEAD', escapeshellarg($sejak)))
            ->output());

        return $keluaran === '' ? '' : $keluaran;
    }

    private function catatanDeploy(array $berkas, string $commit = ''): string
    {
        $daftar = implode("\n", array_map(fn ($b) => '  - '.$b, array_slice($berkas, 0, 200)));
        $sisa = count($berkas) > 200 ? "\n  ... dan ".(count($berkas) - 200)." berkas lain" : '';

        // Langkah yang ditulis sebagai opsional gampang terlewat, dan akibatnya
        // baru terasa sebagai galat 500 di halaman yang memakai paket baru.
        $wajib = '';

        if (in_array('composer.json', $berkas, true) || in_array('composer.lock', $berkas, true)) {
            $wajib .= "!! PAKET INI MENGUBAH DEPENDENSI\n"
                ."   \"composer install --no-dev --optimize-autoloader\" WAJIB dijalankan.\n"
                ."   Tanpa itu, halaman yang memakai paket baru akan galat 500.\n\n";
        }

        if (array_filter($berkas, fn ($b) => str_starts_with($b, 'database/migrations/'))) {
            $wajib .= "!! PAKET INI BERISI MIGRASI BARU\n"
                ."   \"php artisan migrate --force\" WAJIB dijalankan.\n\n";
        }

        // Daftar commit membuat paket yang terlewat langsung kelihatan: kalau
        // commit terbawah di sini bukan yang terakhir Anda pasang, ada paket
        // sebelumnya yang belum sempat diunggah.
        $riwayat = $commit === '' ? '' : "COMMIT YANG TERCAKUP
--------------------
{$commit}

";

        return <<<TXT
        Market ArahInn — paket rilis
        Dibuat: {$this->waktu()}
        Tujuan: https://market.arahinn.com

        {$wajib}{$riwayat}LANGKAH DEPLOY
        --------------
        1. Backup dulu folder aplikasi dan database di server.
        2. Ekstrak isi ZIP ini ke ROOT aplikasi di server (menimpa berkas lama).
           Jangan sertakan/menimpa berkas .env — kredensial produksi ada di sana.
        3. Jalankan di root aplikasi:

               composer install --no-dev --optimize-autoloader
               php artisan migrate --force
               php artisan optimize

           Jalankan "php artisan optimize" SETELAH seluruh berkas terekstrak.
           Perintah itu meng-cache config, rute, dan view sekaligus; menjalankannya
           lebih dulu membuat rute baru tidak ikut terbaca.

        4. Pastikan storage/ dan bootstrap/cache/ dapat ditulis oleh web server:

               chmod -R 775 storage bootstrap/cache

        5. Bila ini pemasangan pertama, buat symlink penyimpanan:

               php artisan storage:link

        CATATAN
        -------
        - vendor/ dan node_modules/ TIDAK disertakan; pasang lewat composer/npm.
        - Aset frontend di public/build sudah dikompilasi, npm tidak perlu di server.
        - Bila tampilan lama masih muncul, kosongkan cache peramban atau CDN.

        ISI PAKET ({$this->jumlah($berkas)} berkas)
        --------------------------------------------
        {$daftar}{$sisa}
        TXT;
    }

    private function waktu(): string
    {
        return now()->translatedFormat('d F Y H:i');
    }

    private function jumlah(array $berkas): int
    {
        return count($berkas);
    }
}
