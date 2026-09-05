<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::withCount('produks')->orderBy('nama')->get();

        return view('admin.kategori.index', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['nama']);

        if (Kategori::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-'.Str::lower(Str::random(4));
        }

        Kategori::create($data);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Kategori $kategori)
    {
        $data = $this->validated($request);
        $kategori->update($data);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori)
    {
        if ($kategori->produks()->exists()) {
            return back()->with('error', 'Kategori masih memiliki produk, tidak dapat dihapus.');
        }

        $kategori->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    public function toggle(Kategori $kategori)
    {
        $kategori->update(['aktif' => ! $kategori->aktif]);

        return back()->with('success', 'Status kategori diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'ikon' => ['nullable', 'string', 'max:20'],
        ]);
    }
}