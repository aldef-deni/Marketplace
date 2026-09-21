<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran lewat gerbang Midtrans, di samping metode manual yang sudah ada.
 *
 * Metode lama tidak diusik. Transfer bank, e-wallet manual, dan COD tetap
 * berjalan persis seperti sebelumnya; yang bertambah hanyalah dua metode baru
 * yang tidak menunggu bukti unggahan karena Midtrans sendiri yang mengabarkan
 * lunas atau tidaknya.
 *
 * Kolom tipe sengaja tidak disentuh. Mengubah enum di MySQL berarti menulis
 * ulang tabelnya, dan tipe di sini memang masih menggambarkan bentuk
 * pembayarannya — yang berbeda adalah siapa yang memverifikasi, dan itulah
 * yang dicatat kolom gateway.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->kolomMetode();
        $this->kolomPembayaran();
        $this->isiMetodeMidtrans();
    }

    public function down(): void
    {
        DB::table('metode_pembayarans')->where('gateway', 'midtrans')->delete();

        Schema::table('metode_pembayarans', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'saluran']);
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropColumn([
                'gateway', 'order_id_gateway', 'kanal_gateway',
                'snap_token', 'snap_url', 'payload_gateway',
            ]);
        });
    }

    private function kolomMetode(): void
    {
        if (Schema::hasColumn('metode_pembayarans', 'gateway')) {
            return;
        }

        Schema::table('metode_pembayarans', function (Blueprint $table) {
            // null = diverifikasi manual oleh admin, seperti sebelumnya.
            $table->string('gateway', 30)->nullable()->after('tipe');
            // Daftar kanal Snap yang boleh muncul, disimpan sebagai JSON.
            $table->text('saluran')->nullable()->after('gateway');
        });
    }

    private function kolomPembayaran(): void
    {
        if (Schema::hasColumn('pembayarans', 'gateway')) {
            return;
        }

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('gateway', 30)->nullable()->after('metode_pembayaran_id');

            // order_id yang dikirim ke Midtrans. Dipisah dari kolom kode karena
            // percobaan bayar ulang wajib memakai order_id baru — Midtrans
            // menolak order_id yang sudah pernah dipakai.
            $table->string('order_id_gateway')->nullable()->unique()->after('gateway');

            // Kanal yang akhirnya benar-benar dipakai pembeli (gopay, qris, ...),
            // baru diketahui saat notifikasi datang.
            $table->string('kanal_gateway', 40)->nullable()->after('order_id_gateway');

            $table->string('snap_token')->nullable()->after('kanal_gateway');
            $table->text('snap_url')->nullable()->after('snap_token');

            // Notifikasi terakhir apa adanya, untuk menelusuri sengketa.
            $table->text('payload_gateway')->nullable()->after('snap_url');
        });
    }

    /**
     * Dua metode yang diminta: QRIS dan E-Wallet, tidak lebih.
     *
     * Kanal di luar daftar izin pada config/midtrans.php akan disaring lagi
     * sebelum dikirim, jadi baris ini bukan satu-satunya penjaga.
     */
    private function isiMetodeMidtrans(): void
    {
        $metode = [
            [
                'nama' => 'QRIS',
                'label_pendek' => 'QRIS',
                'tipe' => 'ewallet',
                'gateway' => 'midtrans',
                'saluran' => json_encode(['other_qris']),
                'warna' => '#E30613',
                'instruksi' => 'Pindai kode QR dengan aplikasi bank atau e-wallet apa pun yang mendukung QRIS. Pembayaran tercatat otomatis.',
                'aktif' => true,
            ],
            [
                'nama' => 'E-Wallet',
                'label_pendek' => 'E-Wallet',
                'tipe' => 'ewallet',
                'gateway' => 'midtrans',
                'saluran' => json_encode(['gopay', 'shopeepay', 'dana', 'ovo']),
                'warna' => '#00AED6',
                'instruksi' => 'Bayar lewat GoPay, ShopeePay, DANA, atau OVO. Pembayaran tercatat otomatis tanpa perlu unggah bukti.',
                'aktif' => true,
            ],
        ];

        foreach ($metode as $baris) {
            $sudahAda = DB::table('metode_pembayarans')
                ->where('gateway', 'midtrans')
                ->where('nama', $baris['nama'])
                ->exists();

            if ($sudahAda) {
                continue;
            }

            DB::table('metode_pembayarans')->insert($baris + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
