<?php

namespace App\Http\Controllers;

use App\Models\Keranjang;
use App\Models\Produk;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    public function index()
    {
        $items = auth()->user()->keranjangs()->with('produk.kategori')->get();

        return view('keranjang.index', compact('items'));
    }

    /**
     * Menyimpan centang pilihan.
     *
     * Dipanggil dari halaman keranjang tiap kali centangnya berubah, sehingga
     * pilihannya bertahan saat pembeli berpindah halaman. Dijawab JSON karena
     * pemanggilnya tidak ingin halamannya dimuat ulang — jumlah di ringkasan
     * sudah dihitung di layar.
     */
    public function pilih(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'array'],
            'id.*' => ['integer'],
        ]);

        $milikSendiri = auth()->user()->keranjangs();

        // Disaring lewat relasi pemiliknya, jadi id keranjang orang lain yang
        // diselipkan ke permintaan tidak berpengaruh apa pun.
        (clone $milikSendiri)->update(['dipilih' => false]);
        (clone $milikSendiri)->whereIn('id', $data['id'])->update(['dipilih' => true]);

        return response()->json(['pesan' => 'Pilihan tersimpan.']);
    }

    public function tambah(Request $request, Produk $produk)
    {
        $qty = max(1, min((int) $request->input('qty', 1), $produk->stok));

        if ($produk->status !== 'aktif' || $produk->stok < 1) {
            return back()->with('error', 'Produk sedang tidak tersedia.');
        }

        $item = Keranjang::firstOrNew([
            'user_id' => auth()->id(),
            'produk_id' => $produk->id,
        ]);
        $sebelum = (int) $item->qty;
        $item->qty = min($item->qty + $qty, $produk->stok);
        // Baru dimasukkan berarti memang berniat dibeli.
        $item->dipilih = true;
        $item->save();

        // Yang benar-benar masuk bisa lebih sedikit daripada yang diminta bila
        // keranjangnya sudah berisi produk yang sama.
        if ($item->qty - $sebelum < $qty) {
            return redirect()->route('keranjang.index')
                ->with('info', "Stok {$produk->nama} tinggal {$produk->stok}. Jumlah di keranjang disesuaikan.");
        }

        return redirect()->route('keranjang.index')
            ->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function updateQty(Request $request, Keranjang $item)
    {
        $this->authorizeOwn($item);

        $diminta = (int) $request->input('qty');
        $stok = (int) ($item->produk?->stok ?? 0);

        if ($stok < 1) {
            return back()->with('error', 'Produk ini sedang kosong dan tidak dapat dibeli.');
        }

        $qty = max(1, min($diminta, $stok));

        $item->qty = $qty;
        $item->save();

        // Dipangkas diam-diam membuat pembeli mengira permintaannya diterima,
        // lalu heran melihat angka yang berbeda saat halaman dimuat ulang.
        if ($diminta > $stok) {
            return back()->with('info', "Stok {$item->produk->nama} tinggal {$stok}. Jumlahnya disesuaikan.");
        }

        return back()->with('success', 'Jumlah item diperbarui.');
    }

    public function hapus(Keranjang $item)
    {
        $this->authorizeOwn($item);
        $item->delete();

        return back()->with('success', 'Item dihapus dari keranjang.');
    }

    public function kosongkan()
    {
        auth()->user()->keranjangs()->delete();

        return back()->with('success', 'Keranjang dikosongkan.');
    }

    private function authorizeOwn(Keranjang $item): void
    {
        abort_if((int) $item->user_id !== (int) auth()->id(), 403);
    }
}