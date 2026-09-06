<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Katalog produk di panel.
 *
 * Pemilik toko hanya menyentuh produk lapaknya sendiri. Pembatasnya dipasang di
 * kueri dan di setiap aksi tunggal, bukan sekadar disembunyikan dari tampilan.
 */
class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::with('kategori', 'toko');

        if ($tokoId = $this->tokoPenjual()) {
            $query->where('toko_id', $tokoId);
        } elseif ($request->filled('toko')) {
            $query->where('toko_id', $request->toko);
        }

        if ($request->filled('q')) {
            $query->where('nama', 'like', '%'.trim($request->q).'%');
        }
        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $produks = $query->latest()->paginate(15)->withQueryString();
        $kategoris = Kategori::orderBy('nama')->get();
        $tokos = $this->tokoTersedia();

        return view('admin.produk.index', compact('produks', 'kategoris', 'tokos'));
    }

    public function create()
    {
        return view('admin.produk.form', [
            'produk' => new Produk,
            'kategoris' => Kategori::orderBy('nama')->get(),
            'tokos' => $this->tokoTersedia(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->buatSlug($request->nama);

        if ($request->hasFile('gambar')) {
            // Awalan "uploads/" ikut disimpan supaya asset() menghasilkan URL
            // yang benar; tanpa ini gambar produk berujung 404.
            $data['gambar'] = 'uploads/'.$request->file('gambar')->store('produk', 'uploads');
        }

        Produk::create($data);

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk)
    {
        $this->pastikanBoleh($produk);

        return view('admin.produk.form', [
            'produk' => $produk,
            'kategoris' => Kategori::orderBy('nama')->get(),
            'tokos' => $this->tokoTersedia(),
        ]);
    }

    public function update(Request $request, Produk $produk)
    {
        $this->pastikanBoleh($produk);

        $data = $this->validated($request);
        $data['slug'] = $this->buatSlug($request->nama, $produk->id);

        if ($request->hasFile('gambar')) {
            // Awalan "uploads/" ikut disimpan supaya asset() menghasilkan URL
            // yang benar; tanpa ini gambar produk berujung 404.
            $data['gambar'] = 'uploads/'.$request->file('gambar')->store('produk', 'uploads');
        }

        $produk->update($data);

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        $this->pastikanBoleh($produk);

        $produk->delete();

        return back()->with('success', 'Produk dihapus.');
    }

    public function toggleStatus(Produk $produk)
    {
        $this->pastikanBoleh($produk);

        $produk->update(['status' => $produk->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('success', 'Status produk diperbarui.');
    }

    /* ---------- Kepemilikan toko ---------- */

    /**
     * Id toko milik pemilik toko yang sedang masuk; null bagi superadmin.
     */
    private function tokoPenjual(): ?int
    {
        return auth()->user()->isSuperadmin() ? null : auth()->user()->toko?->id;
    }

    private function tokoTersedia()
    {
        return auth()->user()->isSuperadmin()
            ? Toko::orderBy('nama')->get()
            : Toko::where('user_id', auth()->id())->get();
    }

    private function pastikanBoleh(Produk $produk): void
    {
        $tokoId = $this->tokoPenjual();

        abort_unless($tokoId === null || $produk->toko_id === $tokoId, 403);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'toko_id' => ['required', 'exists:tokos,id'],
            'kategori_id' => ['required', 'exists:kategoris,id'],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'harga' => ['required', 'numeric', 'min:0'],
            'harga_coret' => ['nullable', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'berat' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Pemilik toko tidak boleh menitipkan produk ke lapak lain, berapa pun
        // nilai yang dikirim formulirnya.
        if ($tokoId = $this->tokoPenjual()) {
            $data['toko_id'] = $tokoId;
        }

        return $data;
    }

    private function buatSlug(string $nama, ?int $kecuali = null): string
    {
        $slug = Str::slug($nama);
        $asli = $slug;
        $i = 2;

        while (Produk::where('slug', $slug)->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))->exists()) {
            $slug = $asli.'-'.$i++;
        }

        return $slug;
    }
}