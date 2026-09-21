<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use App\Support\Midtrans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pembayaran lewat gerbang Midtrans.
 *
 * Ini menyangkut uang, jadi yang dijaga paling ketat bukan jalur mulusnya
 * melainkan jalur curangnya: notifikasi palsu, nominal yang diturunkan, dan
 * kabar terlambat yang membatalkan pesanan yang sudah lunas.
 */
class MidtransTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private Pesanan $pesanan;

    private MetodePembayaran $qris;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nyalakanMidtrans();

        $this->pembeli = User::factory()->create(['role' => 'pengguna']);
        $this->qris = MetodePembayaran::where('gateway', 'midtrans')->where('nama', 'QRIS')->firstOrFail();
        $this->pesanan = $this->buatPesanan();
    }

    /**
     * Kredensial uji. Bukan kunci sungguhan — tanda tangan pada pengujian ini
     * dihitung dengan kunci ini juga, jadi keduanya selalu sepadan.
     */
    private function nyalakanMidtrans(): void
    {
        config([
            'midtrans.aktif' => true,
            'midtrans.produksi' => false,
            'midtrans.server_key' => 'SB-Mid-server-KUNCI-UJI',
            'midtrans.client_key' => 'SB-Mid-client-KUNCI-UJI',
        ]);
    }

    private function buatPesanan(int $total = 250000): Pesanan
    {
        $toko = Toko::create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama' => 'Lapak Uji', 'slug' => 'lapak-uji',
            'status' => 'aktif', 'disetujui_at' => now(),
        ]);

        $kategori = Kategori::firstOrCreate(['slug' => 'elektronik'], ['nama' => 'Elektronik']);

        $produk = Produk::create([
            'toko_id' => $toko->id, 'kategori_id' => $kategori->id,
            'nama' => 'Adaptor', 'slug' => 'adaptor-'.uniqid(),
            'harga' => 240000, 'stok' => 10, 'berat' => 500, 'status' => 'aktif',
        ]);

        $alamat = Alamat::create([
            'user_id' => $this->pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji Coba', 'no_hp' => '08120000000',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);

        $pesanan = Pesanan::create([
            'no_invoice' => 'INV-MID-'.strtoupper(uniqid()),
            'user_id' => $this->pembeli->id, 'alamat_id' => $alamat->id,
            'subtotal' => 240000, 'ongkir' => 10000, 'total' => $total,
            'status' => 'menunggu_pembayaran', 'kurir' => 'JNE', 'layanan_kurir' => 'REG',
            'batas_pembayaran' => now()->addDay(),
        ]);

        PesananItem::create([
            'pesanan_id' => $pesanan->id, 'produk_id' => $produk->id,
            'nama_produk' => $produk->nama, 'harga' => 240000, 'qty' => 1, 'subtotal' => 240000,
        ]);

        Pembayaran::create([
            'pesanan_id' => $pesanan->id,
            'metode_pembayaran_id' => $this->qris->id,
            'kode' => 'PAY-'.uniqid(),
            'jumlah' => $total,
            'status' => 'menunggu',
            'gateway' => 'midtrans',
        ]);

        return $pesanan->fresh();
    }

    /**
     * Notifikasi seperti yang dikirim Midtrans, lengkap dengan tanda tangannya.
     *
     * @return array<string, mixed>
     */
    private function notifikasi(string $orderId, string $status, string $gross = '250000.00', array $tambahan = []): array
    {
        $statusCode = $status === 'settlement' ? '200' : '201';

        return array_merge([
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'transaction_status' => $status,
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $orderId.$statusCode.$gross.config('midtrans.server_key')),
        ], $tambahan);
    }

    private function mulaiTagihan(): Pembayaran
    {
        Http::fake([
            '*/snap/v1/transactions' => Http::response([
                'token' => 'token-uji',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/token-uji',
            ]),
        ]);

        $this->actingAs($this->pembeli)->post(route('pesanan.midtrans.mulai', $this->pesanan));

        return $this->pesanan->pembayaran->fresh();
    }

    /* ---------- Metode di checkout ---------- */

    public function test_qris_dan_ewallet_muncul_saat_midtrans_menyala(): void
    {
        $siap = MetodePembayaran::siap()->where('gateway', 'midtrans')->pluck('nama');

        $this->assertEqualsCanonicalizing(['QRIS', 'E-Wallet'], $siap->all());
    }

    public function test_metode_bergerbang_disembunyikan_saat_midtrans_mati(): void
    {
        // Ditawarkan lalu gagal jauh lebih buruk daripada tidak ditawarkan.
        config(['midtrans.aktif' => false]);

        $this->assertSame(0, MetodePembayaran::siap()->whereNotNull('gateway')->count());
    }

    public function test_metode_bergerbang_tidak_menuntut_nomor_rekening(): void
    {
        $this->assertTrue($this->qris->siapDipakai());
        $this->assertNull($this->qris->nomor_rekening);
    }

    /* ---------- Membuat tagihan ---------- */

    public function test_tagihan_dibuat_dengan_kanal_yang_dibatasi(): void
    {
        $this->mulaiTagihan();

        Http::assertSent(function ($request) {
            $isi = $request->data();

            // Inti permintaan pengguna: hanya QRIS dan e-wallet yang boleh
            // muncul, tidak pernah kartu kredit atau virtual account.
            return $isi['enabled_payments'] === ['other_qris']
                && $isi['transaction_details']['gross_amount'] === 250000;
        });
    }

    public function test_rincian_belanja_selalu_sejumlah_tagihannya(): void
    {
        // Midtrans menolak transaksi bila jumlah item_details tidak sama persis
        // dengan gross_amount.
        $this->mulaiTagihan();

        Http::assertSent(function ($request) {
            $isi = $request->data();
            $jumlah = collect($isi['item_details'])->sum(fn ($b) => $b['price'] * $b['quantity']);

            return $jumlah === $isi['transaction_details']['gross_amount'];
        });
    }

    public function test_alamat_bayar_disimpan_dan_pembeli_dialihkan(): void
    {
        Http::fake([
            '*/snap/v1/transactions' => Http::response([
                'token' => 'token-uji',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/token-uji',
            ]),
        ]);

        $this->actingAs($this->pembeli)
            ->post(route('pesanan.midtrans.mulai', $this->pesanan))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v4/redirection/token-uji');

        $this->assertNotNull($this->pesanan->pembayaran->fresh()->order_id_gateway);
    }

    public function test_tagihan_yang_masih_hidup_dipakai_ulang(): void
    {
        $pertama = $this->mulaiTagihan();

        // Tanpa ini, satu pesanan meninggalkan deretan tagihan menggantung di
        // dasbor Midtrans setiap kali tombolnya ditekan.
        Http::fake();
        $this->actingAs($this->pembeli)->post(route('pesanan.midtrans.mulai', $this->pesanan));
        Http::assertNothingSent();

        $this->assertSame($pertama->order_id_gateway, $this->pesanan->pembayaran->fresh()->order_id_gateway);
    }

    public function test_pesanan_orang_lain_tidak_dapat_dibayari(): void
    {
        $orangLain = User::factory()->create(['role' => 'pengguna']);

        $this->actingAs($orangLain)
            ->post(route('pesanan.midtrans.mulai', $this->pesanan))
            ->assertForbidden();
    }

    /* ---------- Notifikasi ---------- */

    public function test_notifikasi_lunas_menaikkan_pesanan_ke_diproses(): void
    {
        $pembayaran = $this->mulaiTagihan();

        $this->postJson(route('midtrans.notifikasi'),
            $this->notifikasi($pembayaran->order_id_gateway, 'settlement'))
            ->assertOk();

        $this->assertSame('dibayar', $pembayaran->fresh()->status);
        $this->assertSame('diproses', $this->pesanan->fresh()->status);
        $this->assertNotNull($pembayaran->fresh()->dibayar_at);
    }

    public function test_notifikasi_tanpa_tanda_tangan_sah_ditolak(): void
    {
        $pembayaran = $this->mulaiTagihan();

        $palsu = $this->notifikasi($pembayaran->order_id_gateway, 'settlement');
        $palsu['signature_key'] = str_repeat('a', 128);

        // Tanpa penjagaan ini, siapa pun yang tahu alamat webhook dapat
        // melunasi pesanan hanya dengan satu POST.
        $this->postJson(route('midtrans.notifikasi'), $palsu)->assertForbidden();

        $this->assertSame('menunggu', $pembayaran->fresh()->status);
        $this->assertSame('menunggu_pembayaran', $this->pesanan->fresh()->status);
    }

    public function test_notifikasi_dengan_nominal_lebih_kecil_ditolak(): void
    {
        $pembayaran = $this->mulaiTagihan();

        // Tanda tangannya sah — dihitung atas nominal kecil itu — tapi
        // nominalnya tidak sesuai tagihan.
        $this->postJson(route('midtrans.notifikasi'),
            $this->notifikasi($pembayaran->order_id_gateway, 'settlement', '1000.00'))
            ->assertStatus(422);

        $this->assertSame('menunggu', $pembayaran->fresh()->status);
    }

    public function test_notifikasi_kedaluwarsa_membatalkan_dan_mengembalikan_stok(): void
    {
        $pembayaran = $this->mulaiTagihan();
        $item = $this->pesanan->items->first();
        $stokAwal = $item->produk->stok;

        $this->postJson(route('midtrans.notifikasi'),
            $this->notifikasi($pembayaran->order_id_gateway, 'expire'))
            ->assertOk();

        $this->assertSame('dibatalkan', $this->pesanan->fresh()->status);
        $this->assertSame($stokAwal + $item->qty, $item->produk->fresh()->stok);
    }

    public function test_kabar_kedaluwarsa_yang_terlambat_tidak_mencabut_kelunasan(): void
    {
        $pembayaran = $this->mulaiTagihan();

        $this->postJson(route('midtrans.notifikasi'),
            $this->notifikasi($pembayaran->order_id_gateway, 'settlement'))->assertOk();

        // Midtrans mengirim kabarnya berkali-kali dan urutannya tidak dijamin.
        $this->postJson(route('midtrans.notifikasi'),
            $this->notifikasi($pembayaran->order_id_gateway, 'expire'))->assertOk();

        $this->assertSame('dibayar', $pembayaran->fresh()->status);
        $this->assertSame('diproses', $this->pesanan->fresh()->status);
    }

    public function test_notifikasi_berulang_tidak_menggandakan_apa_pun(): void
    {
        $pembayaran = $this->mulaiTagihan();
        $kabar = $this->notifikasi($pembayaran->order_id_gateway, 'settlement');

        $this->postJson(route('midtrans.notifikasi'), $kabar)->assertOk();
        $waktuPertama = $pembayaran->fresh()->dibayar_at;

        $this->postJson(route('midtrans.notifikasi'), $kabar)->assertOk();

        $this->assertEquals($waktuPertama, $pembayaran->fresh()->dibayar_at);
    }

    public function test_capture_yang_ditandai_curang_belum_dianggap_lunas(): void
    {
        $pembayaran = $this->mulaiTagihan();

        $this->postJson(route('midtrans.notifikasi'),
            $this->notifikasi($pembayaran->order_id_gateway, 'capture', '250000.00', [
                'fraud_status' => 'challenge',
            ]))->assertOk();

        $this->assertSame('menunggu', $pembayaran->fresh()->status);
    }

    public function test_order_id_tak_dikenal_dibalas_baik_baik(): void
    {
        // Dibalas 2xx supaya Midtrans berhenti mengulang kabar yang memang
        // tidak akan pernah cocok dengan apa pun di sini.
        $this->postJson(route('midtrans.notifikasi'),
            $this->notifikasi('INV-TIDAK-ADA', 'settlement'))->assertOk();
    }

    /* ---------- Pemeriksaan manual ---------- */

    public function test_pembeli_dapat_meminta_pemeriksaan_status(): void
    {
        $pembayaran = $this->mulaiTagihan();

        Http::fake(['*/v2/*' => Http::response([
            'order_id' => $pembayaran->order_id_gateway,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'gross_amount' => '250000.00',
        ])]);

        $this->actingAs($this->pembeli)
            ->post(route('pesanan.midtrans.periksa', $this->pesanan))
            ->assertRedirect();

        $this->assertSame('dibayar', $pembayaran->fresh()->status);
    }

    /* ---------- Batas dengan verifikasi manual ---------- */

    public function test_pembayaran_bergerbang_tidak_pernah_menunggu_penilaian_admin(): void
    {
        $pembayaran = $this->pesanan->pembayaran;

        // Bahkan bila ada bukti yang entah bagaimana terlampir, tombol
        // verifikasi manual tidak boleh muncul — itu jalan pintas untuk
        // meluluskan pesanan yang belum dibayar.
        $pembayaran->update(['bukti' => 'uploads/bukti/palsu.jpg']);

        $this->assertFalse($pembayaran->fresh()->menungguPenilaian());
    }

    /* ---------- Penyaringan kanal ---------- */

    public function test_kanal_di_luar_daftar_izin_disaring_sebelum_dikirim(): void
    {
        // Satu baris data yang salah di tabel metode tidak boleh diam-diam
        // memunculkan kartu kredit di halaman Midtrans.
        $this->qris->update(['saluran' => ['other_qris', 'credit_card', 'bca_va']]);

        $this->mulaiTagihan();

        Http::assertSent(fn ($request) => $request->data()['enabled_payments'] === ['other_qris']);
    }

    public function test_metode_tanpa_kanal_sah_tidak_membuat_tagihan(): void
    {
        $this->qris->update(['saluran' => ['credit_card']]);

        Http::fake();

        $this->actingAs($this->pembeli)
            ->post(route('pesanan.midtrans.mulai', $this->pesanan))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    /* ---------- Terjemahan status ---------- */

    public function test_terjemahan_status_midtrans(): void
    {
        $this->assertSame('dibayar', Midtrans::terjemahkanStatus('settlement'));
        $this->assertSame('dibayar', Midtrans::terjemahkanStatus('capture', 'accept'));
        $this->assertSame('menunggu', Midtrans::terjemahkanStatus('capture', 'challenge'));
        $this->assertSame('menunggu', Midtrans::terjemahkanStatus('pending'));

        foreach (['deny', 'cancel', 'expire', 'failure'] as $status) {
            $this->assertSame('dibatalkan', Midtrans::terjemahkanStatus($status), $status);
        }
    }
}
