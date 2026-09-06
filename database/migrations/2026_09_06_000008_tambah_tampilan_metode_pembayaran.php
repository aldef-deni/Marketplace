<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Dua kolom tampilan untuk metode pembayaran.
 *
 * Lencana metode di footer sebelumnya dibaca dari daftar tetap di config, yang
 * sama sekali tidak berhubungan dengan tabel ini. Akibatnya footer memajang
 * merchant yang tidak pernah dipasang nomornya, dan tidak ikut berubah ketika
 * metodenya dinonaktifkan. Supaya footer bisa membaca tabel ini, dibutuhkan
 * nama pendek untuk lencananya dan warna khas merchantnya.
 */
return new class extends Migration
{
    /**
     * Warna khas merchant yang sudah dikenal, dipakai mengisi data lama.
     */
    private const PALET = [
        'BCA' => '#0060AF',
        'Mandiri' => '#003D79',
        'BRI' => '#00529C',
        'BNI' => '#F05A22',
        'GoPay' => '#00AED6',
        'OVO' => '#4C3494',
        'DANA' => '#118EEA',
        'ShopeePay' => '#EE4D2D',
        'LinkAja' => '#E82526',
        'COD' => '#0E9F6E',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('metode_pembayarans', 'label_pendek')) {
            return;
        }

        Schema::table('metode_pembayarans', function (Blueprint $table) {
            $table->string('label_pendek', 30)->nullable()->after('nama');
            $table->string('warna', 7)->nullable()->after('logo');
        });

        $this->isiDataLama();
    }

    public function down(): void
    {
        Schema::table('metode_pembayarans', function (Blueprint $table) {
            $table->dropColumn(['label_pendek', 'warna']);
        });
    }

    /**
     * Isi nama pendek dan warna untuk baris yang sudah ada.
     *
     * Nama seperti "Transfer Bank BCA" terlalu panjang untuk lencana footer,
     * jadi nama merchantnya dicari di dalam nama lengkapnya.
     */
    private function isiDataLama(): void
    {
        foreach (DB::table('metode_pembayarans')->get() as $metode) {
            $cocok = null;

            foreach (array_keys(self::PALET) as $merchant) {
                if (Str::contains($metode->nama, $merchant, ignoreCase: true)) {
                    $cocok = $merchant;
                    break;
                }
            }

            DB::table('metode_pembayarans')->where('id', $metode->id)->update([
                // Tanpa merchant yang dikenali, kata pertama namanya sudah cukup
                // sebagai lencana — lebih baik daripada memaksa nama panjang.
                'label_pendek' => $cocok ?? Str::limit(Str::before($metode->nama, ' ('), 20, ''),
                'warna' => self::PALET[$cocok] ?? null,
            ]);
        }
    }
};
