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
        $produk = new Produk;

        return view('admin.produk.form', [
            'produk' => $produk,
            'kategoris' => Kategori::orderBy('nama')->get(),
            'tokos' => $this->tokoTersedia(),
            'kunciUtama' => $this->kunciUtama($produk),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->buatSlug($request->nama);

        $produk = Produk::create($data);
        $this->simpanGaleri($request, $produk);

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk)
    {
        $this->pastikanBoleh($produk);

        return view('admin.produk.form', [
            'produk' => $produk->load('gambars'),
            'kategoris' => Kategori::orderBy('nama')->get(),
            'tokos' => $this->tokoTersedia(),
            'kunciUtama' => $this->kunciUtama($produk),
        ]);
    }

    public function update(Request $request, Produk $produk)
    {
        $this->pastikanBoleh($produk);

        $data = $this->validated($request);
        $data['slug'] = $this->buatSlug($request->nama, $produk->id);

        $produk->update($data);
        $this->simpanGaleri($request, $produk);

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

    /* ---------- Galeri gambar ---------- */

    /**
     * Penanda gambar bawaan bagi formulir, dalam bentuk "lama:<id>".
     *
     * Kolom produks.gambar menyimpan jalur, sedangkan formulir menunjuk barisnya
     * — jadi jalur itu perlu dicocokkan kembali ke galeri di sini.
     */
    private function kunciUtama(Produk $produk): string
    {
        if (($lama = (string) old('gambar_utama')) !== '' && str_starts_with($lama, 'lama:')) {
            return $lama;
        }

        if (! $produk->exists || ! $produk->gambar) {
            return '';
        }

        $baris = $produk->gambars->firstWhere('jalur', $produk->gambar);

        return $baris ? 'lama:'.$baris->id : '';
    }

    /**
     * Menerapkan seluruh perubahan gambar dari satu kali kirim formulir:
     * unggahan baru, gambar yang dibuang, dan pilihan gambar bawaan.
     *
     * Ketiganya sengaja ditangani bersama, bukan lewat rute terpisah, supaya
     * pemilik toko dapat menata galerinya sekaligus saat menambah produk baru —
     * sebelum produknya punya id, rute per-gambar belum ada yang bisa dituju.
     */
    private function simpanGaleri(Request $request, Produk $produk): void
    {
        $this->buangGambar($request, $produk);

        $baru = $this->unggahGambar($request, $produk);

        $produk->load('gambars');
        $produk->update(['gambar' => $this->tentukanUtama($request, $produk, $baru)]);
    }

    /**
     * Melepas gambar yang dicentang untuk dibuang.
     *
     * Berkasnya sendiri dibiarkan di disk: item pesanan menyalin jalur gambar
     * saat pesanan dibuat, jadi menghapus berkasnya akan mengosongkan gambar
     * pada riwayat belanja yang sudah terlanjur tercetak.
     */
    private function buangGambar(Request $request, Produk $produk): void
    {
        $dibuang = array_filter((array) $request->input('buang_gambar', []));

        if ($dibuang === []) {
            return;
        }

        // Disaring lewat relasinya, jadi id milik produk lain tidak berpengaruh.
        $produk->gambars()->whereIn('id', $dibuang)->delete();
    }

    /**
     * Menyimpan berkas yang baru diunggah, meneruskan urutan yang sudah ada.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\ProdukGambar>
     *                                              berurut sesuai urutan berkas pada formulir
     */
    private function unggahGambar(Request $request, Produk $produk): \Illuminate\Support\Collection
    {
        $urutan = (int) $produk->gambars()->max('urutan');

        return collect($request->file('gambar', []))
            ->filter()
            ->map(function ($berkas) use ($produk, &$urutan) {
                // Awalan "uploads/" ikut disimpan supaya asset() menghasilkan URL
                // yang benar; tanpa ini gambar produk berujung 404.
                return $produk->gambars()->create([
                    'jalur' => 'uploads/'.$berkas->store('produk', 'uploads'),
                    'urutan' => ++$urutan,
                ]);
            })
            ->values();
    }

    /**
     * Jalur gambar yang menjadi bawaan produk.
     *
     * Pilihan dari formulir berbentuk "lama:<id>" untuk gambar yang sudah
     * tersimpan atau "baru:<indeks>" untuk salah satu berkas yang barusan
     * diunggah — saat formulir dikirim, gambar baru memang belum punya id.
     *
     * Bila pilihannya tidak ada (mis. gambar itu ikut dibuang pada kiriman yang
     * sama), gambar pertama yang tersisa yang dipakai. Produk tanpa gambar sama
     * sekali mengembalikan null, bukan jalur yang menunjuk berkas terhapus.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\ProdukGambar>  $baru
     */
    private function tentukanUtama(Request $request, Produk $produk, $baru): ?string
    {
        $pilihan = (string) $request->input('gambar_utama', '');

        if (str_starts_with($pilihan, 'baru:')) {
            $jalur = $baru->get((int) substr($pilihan, 5))?->jalur;
        } elseif (str_starts_with($pilihan, 'lama:')) {
            $jalur = $produk->gambars->firstWhere('id', (int) substr($pilihan, 5))?->jalur;
        } else {
            // Tidak ada pilihan: pertahankan gambar bawaan yang lama selama ia
            // masih ada di galeri.
            $jalur = $produk->gambars->contains('jalur', $produk->gambar) ? $produk->gambar : null;
        }

        return $jalur ?? $produk->gambars->first()?->jalur;
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
            'gambar' => ['nullable', 'array', 'max:8'],
            'gambar.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'buang_gambar' => ['nullable', 'array'],
            'buang_gambar.*' => ['integer'],
            'gambar_utama' => ['nullable', 'string', 'max:30'],
        ], [
            'gambar.max' => 'Maksimal 8 gambar untuk satu produk.',
            'gambar.*.image' => 'Berkas :position bukan gambar.',
            'gambar.*.mimes' => 'Gambar :position harus berformat JPG, PNG, atau WebP.',
            'gambar.*.max' => 'Ukuran gambar :position melebihi 2MB.',
        ]);

        // Gambar diurus tersendiri lewat tabel galeri; kolom produks.gambar
        // hanya menampung jalur yang terpilih sebagai bawaan.
        unset($data['gambar'], $data['gambar.*'], $data['buang_gambar'], $data['gambar_utama']);

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