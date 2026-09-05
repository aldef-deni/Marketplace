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
        $item->qty = min($item->qty + $qty, $produk->stok);
        $item->save();

        return redirect()->route('keranjang.index')
            ->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function updateQty(Request $request, Keranjang $item)
    {
        $this->authorizeOwn($item);

        $qty = (int) $request->input('qty');
        $item->qty = max(1, min($qty, $item->produk->stok));
        $item->save();

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
        abort_if($item->user_id !== auth()->id(), 403);
    }
}