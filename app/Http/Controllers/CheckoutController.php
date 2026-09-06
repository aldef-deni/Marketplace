<?php

namespace App\Http\Controllers;

use App\Models\Alamat;
use App\Models\MetodePembayaran;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Support\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Daftar kurir pengiriman: nama => [layanan, tarif per kg, estimasi].
     */
    public const KURIR = [
        'JNE' => ['layanan' => 'REG', 'tarif' => 10000, 'estimasi' => '2 - 3 hari'],
        'J&T Express' => ['layanan' => 'EZ', 'tarif' => 12000, 'estimasi' => '2 - 3 hari'],
        'SiCepat' => ['layanan' => 'REG', 'tarif' => 11000, 'estimasi' => '2 - 4 hari'],
        'POS Indonesia' => ['layanan' => 'Kilat Khusus', 'tarif' => 9000, 'estimasi' => '3 - 5 hari'],
    ];

    public function index()
    {
        $items = auth()->user()->keranjangs()->with('produk.kategori')->get();

        if ($items->isEmpty()) {
            return redirect()->route('produk.index')->with('info', 'Keranjang Anda masih kosong.');
        }

        $alamats = auth()->user()->alamats()->get();
        $metodes = MetodePembayaran::siap()->get();

        $subtotal = $items->sum(fn ($i) => $i->produk->hargaEfektif() * $i->qty);
        $beratGram = $items->sum(fn ($i) => $i->produk->berat * $i->qty);

        return view('checkout.index', compact('items', 'alamats', 'metodes', 'subtotal', 'beratGram'));
    }

    public function store(Request $request)
    {
        $items = auth()->user()->keranjangs()->with('produk')->get();

        if ($items->isEmpty()) {
            return redirect()->route('produk.index')->with('info', 'Keranjang Anda masih kosong.');
        }

        $validated = $request->validate([
            'alamat_id' => ['required', 'exists:alamats,id'],
            'kurir' => ['required', 'in:'.implode(',', array_keys(self::KURIR))],
            'metode_pembayaran_id' => ['required', 'exists:metode_pembayarans,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $alamat = Alamat::where('id', $validated['alamat_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Disaring ulang di sini, bukan hanya di formulir: metode yang nomornya
        // dikosongkan sesudah halaman dimuat tidak boleh tetap lolos.
        $metode = MetodePembayaran::siap()
            ->where('id', $validated['metode_pembayaran_id'])
            ->firstOrFail();

        $subtotal = 0;
        $beratGram = 0;

        foreach ($items as $item) {
            abort_if($item->produk->status !== 'aktif' || $item->produk->stok < $item->qty, 422,
                "Stok produk {$item->produk->nama} tidak mencukupi.");
            $subtotal += $item->produk->hargaEfektif() * $item->qty;
            $beratGram += $item->produk->berat * $item->qty;
        }

        $ongkir = $this->hitungOngkir($validated['kurir'], $beratGram);

        return \DB::transaction(function () use ($items, $validated, $alamat, $metode, $subtotal, $ongkir) {
            $pesanan = Pesanan::create([
                'no_invoice' => $this->buatNoInvoice(),
                'user_id' => auth()->id(),
                'alamat_id' => $alamat->id,
                'subtotal' => $subtotal,
                'ongkir' => $ongkir,
                'total' => $subtotal + $ongkir,
                'status' => $metode->tipe === 'cod' ? 'menunggu_konfirmasi' : 'menunggu_pembayaran',
                'kurir' => $validated['kurir'],
                'layanan_kurir' => self::KURIR[$validated['kurir']]['layanan'],
                'catatan' => $validated['catatan'] ?? null,
                'batas_pembayaran' => $metode->tipe === 'cod' ? null : Carbon::now()->addHours(24),
            ]);

            foreach ($items as $item) {
                PesananItem::create([
                    'pesanan_id' => $pesanan->id,
                    'produk_id' => $item->produk_id,
                    'nama_produk' => $item->produk->nama,
                    'gambar' => $item->produk->gambar,
                    'harga' => $item->produk->hargaEfektif(),
                    'qty' => $item->qty,
                    'subtotal' => $item->produk->hargaEfektif() * $item->qty,
                ]);

                // Kurangi stok sebagai bentuk reservasi
                $item->produk->decrement('stok', $item->qty);

                // Kuota potongan ikut terpakai — flash sale maupun promo. Tanpa
                // pencatatan ini, harga promo berlaku tanpa batas dan kuotanya
                // kehilangan arti. Yang dikurangi hanya potongan yang benar-benar
                // dipakai, bukan setiap potongan yang kebetulan berlaku.
                if ($potongan = $item->produk->potonganBerlaku()) {
                    $potongan->sumber->increment('terjual', $potongan->sisaKuota === null
                        ? $item->qty
                        : min($item->qty, $potongan->sisaKuota));
                }
            }

            Pembayaran::create([
                'pesanan_id' => $pesanan->id,
                'metode_pembayaran_id' => $metode->id,
                'kode' => 'PAY-'.$pesanan->no_invoice.'-'.Str::upper(Str::random(4)),
                'jumlah' => $subtotal + $ongkir,
                'status' => $metode->tipe === 'cod' ? 'menunggu' : 'menunggu',
                'keterangan' => $metode->tipe === 'cod' ? 'Pembayaran dilakukan saat pesanan diterima (COD).' : null,
            ]);

            auth()->user()->keranjangs()->delete();

            $pesanan->load('user');
            Notifikasi::kePembeli($pesanan, 'pesanan_dibuat');
            Notifikasi::keAdmin($pesanan, 'pesanan_baru');

            return redirect()->route('pesanan.show', $pesanan->no_invoice)
                ->with('success', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran Anda.');
        });
    }

    public static function hitungOngkir(string $kurir, int $beratGram): int
    {
        $config = self::KURIR[$kurir] ?? self::KURIR['JNE'];
        $beratKg = max(1, $beratGram / 1000);

        return bulatkanRibuan($beratKg * $config['tarif']);
    }

    private function buatNoInvoice(): string
    {
        $tanggal = Carbon::now()->format('Ymd');
        $prefix = 'INV-'.$tanggal.'-';
        $terakhir = Pesanan::where('no_invoice', 'like', $prefix.'%')
            ->latest('id')
            ->value('no_invoice');

        $urutan = $terakhir ? (int) Str::afterLast($terakhir, '-') + 1 : 1;

        return $prefix.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }
}