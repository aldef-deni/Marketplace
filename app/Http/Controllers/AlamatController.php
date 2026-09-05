<?php

namespace App\Http\Controllers;

use App\Models\Alamat;
use Illuminate\Http\Request;

class AlamatController extends Controller
{
    public function index()
    {
        $alamats = auth()->user()->alamats()->latest()->get();

        return view('alamat.index', compact('alamats'));
    }

    public function create()
    {
        return view('alamat.form', ['alamat' => new Alamat]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->boolean('is_default') || auth()->user()->alamats()->count() === 0) {
            auth()->user()->alamats()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        auth()->user()->alamats()->create($data);

        return redirect()->route('alamat.index')->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function edit(Alamat $alamat)
    {
        $this->authorizeOwn($alamat);

        return view('alamat.form', compact('alamat'));
    }

    public function update(Request $request, Alamat $alamat)
    {
        $this->authorizeOwn($alamat);

        $data = $this->validated($request);

        if ($request->boolean('is_default')) {
            auth()->user()->alamats()->where('id', '!=', $alamat->id)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $alamat->update($data);

        return redirect()->route('alamat.index')->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(Alamat $alamat)
    {
        $this->authorizeOwn($alamat);
        $alamat->delete();

        return back()->with('success', 'Alamat dihapus.');
    }

    public function jadikanDefault(Alamat $alamat)
    {
        $this->authorizeOwn($alamat);

        auth()->user()->alamats()->update(['is_default' => false]);
        $alamat->update(['is_default' => true]);

        return back()->with('success', 'Alamat utama diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'nama_penerima' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'string', 'max:20'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'alamat_lengkap' => ['required', 'string', 'max:500'],
        ]);
    }

    private function authorizeOwn(Alamat $alamat): void
    {
        abort_if($alamat->user_id !== auth()->id(), 403);
    }
}