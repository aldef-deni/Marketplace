<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::with('kategori');

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

        return view('admin.produk.index', compact('produks', 'kategoris'));
    }

    public function create()
    {
        $kategoris = Kategori::orderBy('nama')->get();

        return view('admin.produk.form', ['produk' => new Produk, 'kategoris' => $kategoris]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->buatSlug($request->nama);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('produk', 'uploads');
        }

        Produk::create($data);

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk)
    {
        $kategoris = Kategori::orderBy('nama')->get();

        return view('admin.produk.form', compact('produk', 'kategoris'));
    }

    public function update(Request $request, Produk $produk)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->buatSlug($request->nama, $produk->id);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('produk', 'uploads');
        }

        $produk->update($data);

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        $produk->delete();

        return back()->with('success', 'Produk dihapus.');
    }

    public function toggleStatus(Produk $produk)
    {
        $produk->update(['status' => $produk->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('success', 'Status produk diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
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