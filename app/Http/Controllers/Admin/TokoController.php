<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengelolaan toko di panel.
 *
 * Pengelola platform melihat seluruh toko dan menyetujui yang baru mendaftar.
 * Penjual hanya melihat dan menyunting tokonya sendiri — pembatasnya dipasang
 * pada kuerinya, bukan hanya disembunyikan dari tampilan.
 */
class TokoController extends Controller
{
    public function index(Request $request)
    {
        $query = Toko::with('pemilik')
            ->withCount(['produks' => fn ($q) => $q->where('status', 'aktif')]);

        $this->batasiMilikSendiri($query);

        if ($request->filled('q')) {
            $kata = trim($request->q);
            $query->where(fn ($q) => $q->where('nama', 'like', "%{$kata}%")->orWhere('kota', 'like', "%{$kata}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tokos = $query->latest()->paginate(15)->withQueryString();

        $jumlah = [
            'semua' => $this->hitung(),
            'aktif' => $this->hitung('aktif'),
            'menunggu' => $this->hitung('menunggu'),
            'nonaktif' => $this->hitung('nonaktif'),
        ];

        return view('admin.toko.index', compact('tokos', 'jumlah'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.toko.form', [
            'toko' => new Toko,
            'pemiliks' => $this->calonPemilik(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $this->validasi($request);
        $data['slug'] = Toko::slugUnik($data['nama']);

        $this->simpanBerkas($request, $data);

        // Toko yang dibuat pengelola langsung aktif; tidak ada yang perlu
        // disetujui ketika pembuatnya sendiri sudah pengelola platform.
        $data['status'] = 'aktif';
        $data['disetujui_at'] = now();

        $toko = Toko::create($data);

        // Pemiliknya dinaikkan jadi penjual bila sebelumnya pembeli biasa,
        // supaya ia benar-benar bisa masuk ke panel tokonya.
        if ($toko->pemilik?->isPengguna()) {
            $toko->pemilik->update(['role' => 'penjual']);
        }

        return redirect()->route('admin.toko.index')->with('success', 'Toko berhasil dibuat.');
    }

    public function edit(Toko $toko)
    {
        $this->pastikanBoleh($toko);

        return view('admin.toko.form', [
            'toko' => $toko,
            'pemiliks' => $this->calonPemilik(),
        ]);
    }

    public function update(Request $request, Toko $toko)
    {
        $this->pastikanBoleh($toko);

        $data = $this->validasi($request, $toko);

        if ($data['nama'] !== $toko->nama) {
            $data['slug'] = Toko::slugUnik($data['nama'], $toko->id);
        }

        // Penjual tidak boleh memindahkan tokonya ke orang lain.
        if (! auth()->user()->isAdmin()) {
            unset($data['user_id']);
        }

        $this->simpanBerkas($request, $data);

        $toko->update($data);

        return redirect()->route('admin.toko.index')->with('success', 'Toko diperbarui.');
    }

    /**
     * Setujui, tangguhkan, atau aktifkan kembali sebuah toko.
     */
    public function toggleStatus(Toko $toko)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $aktif = ! $toko->aktif();

        $toko->update([
            'status' => $aktif ? 'aktif' : 'nonaktif',
            'disetujui_at' => $aktif ? ($toko->disetujui_at ?? now()) : $toko->disetujui_at,
        ]);

        return back()->with('success', $aktif
            ? 'Toko diaktifkan dan kini tampil di etalase.'
            : 'Toko ditangguhkan. Produknya ikut disembunyikan dari etalase.');
    }

    public function destroy(Toko $toko)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($toko->produks()->exists()) {
            return back()->with('error', 'Toko masih memiliki produk. Pindahkan atau hapus produknya dulu.');
        }

        $toko->delete();

        return back()->with('success', 'Toko dihapus.');
    }

    /* ---------- Penopang ---------- */

    private function batasiMilikSendiri($query): void
    {
        if (! auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }
    }

    private function hitung(?string $status = null): int
    {
        $query = Toko::query()->when($status, fn ($q) => $q->where('status', $status));
        $this->batasiMilikSendiri($query);

        return $query->count();
    }

    private function pastikanBoleh(Toko $toko): void
    {
        abort_unless(auth()->user()->isAdmin() || $toko->user_id === auth()->id(), 403);
    }

    private function calonPemilik()
    {
        return auth()->user()->isAdmin()
            ? User::whereIn('role', ['penjual', 'pengguna', 'admin', 'superadmin'])->orderBy('name')->get()
            : collect();
    }

    private function validasi(Request $request, ?Toko $abaikan = null): array
    {
        return $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'nama' => ['required', 'string', 'max:120', Rule::unique('tokos', 'nama')->ignore($abaikan?->id)],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'provinsi' => ['nullable', 'string', 'max:80'],
            'kota' => ['nullable', 'string', 'max:80'],
            'kecamatan' => ['nullable', 'string', 'max:80'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'nama.unique' => 'Sudah ada toko dengan nama tersebut.',
        ]);
    }

    private function simpanBerkas(Request $request, array &$data): void
    {
        foreach (['logo', 'banner'] as $berkas) {
            if ($request->hasFile($berkas)) {
                // Awalan "uploads/" ikut disimpan, seperti berkas lain di sistem
                // ini, supaya asset() menghasilkan URL yang benar.
                $data[$berkas] = 'uploads/'.$request->file($berkas)->store('toko', 'uploads');
            } else {
                unset($data[$berkas]);
            }
        }
    }
}
