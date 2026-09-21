<?php

namespace App\Http\Controllers;

use App\Models\MetodePembayaran;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Pengiriman;
use App\Support\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PesananController extends Controller
{
    public function index()
    {
        $status = request('status');

        $pesanans = auth()->user()->pesanans()
            ->with('items', 'pembayaran.metodePembayaran', 'pengiriman')
            ->when($status && array_key_exists($status, Pesanan::STATUS), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pesanan.index', compact('pesanans', 'status'));
    }

    public function show(string $noInvoice)
    {
        $pesanan = Pesanan::with([
            'items', 'alamat', 'pembayaran.metodePembayaran', 'pengiriman',
        ])->where('no_invoice', $noInvoice)->firstOrFail();

        if ($milikOrangLain = $this->bukanMilikSendiri($pesanan)) {
            return $milikOrangLain;
        }

        // Pilihan metode hanya berguna selama pesanannya memang masih menunggu
        // dibayar; di luar itu daftarnya tidak perlu ikut dimuat.
        $metodes = $pesanan->bolehGantiMetode()
            ? MetodePembayaran::siap()->get()
            : collect();

        return view('pesanan.show', compact('pesanan', 'metodes'));
    }

    /**
     * Mengganti metode pembayaran pesanan yang belum dibayar.
     *
     * Tanpa ini, pembeli yang salah pilih metode hanya punya satu jalan keluar:
     * membatalkan pesanannya lalu menyusunnya ulang dari awal.
     */
    public function ubahMetode(Request $request, Pesanan $pesanan)
    {
        $this->authorizeOwn($pesanan);

        abort_unless($pesanan->bolehGantiMetode(), 422,
            'Metode pembayaran tidak dapat diubah pada keadaan ini.');

        $data = $request->validate([
            'metode_pembayaran_id' => ['required', 'exists:metode_pembayarans,id'],
        ]);

        // Disaring ulang di sini: metode yang nomornya dikosongkan sesudah
        // halaman dimuat tidak boleh tetap lolos.
        $metode = MetodePembayaran::siap()->where('id', $data['metode_pembayaran_id'])->firstOrFail();

        $pembayaran = $pesanan->pembayaran;

        if ($pembayaran->metode_pembayaran_id === $metode->id) {
            return back()->with('info', 'Metode pembayaran tidak berubah.');
        }

        DB::transaction(function () use ($pesanan, $pembayaran, $metode) {
            $pembayaran->update([
                'metode_pembayaran_id' => $metode->id,
                'gateway' => $metode->gateway,

                // Bukti untuk metode lama tidak berlaku lagi bagi metode baru.
                'bukti' => null,
                'nama_pengirim' => null,

                // Tagihan Midtrans yang lama ditinggalkan, bukan dipakai ulang:
                // ia terikat pada kanal metode sebelumnya. Tagihannya gugur
                // sendiri saat masa berlakunya habis.
                'order_id_gateway' => null,
                'snap_token' => null,
                'snap_url' => null,

                'status' => 'menunggu',
                'keterangan' => $metode->tipe === 'cod'
                    ? 'Pembayaran dilakukan saat pesanan diterima (COD).'
                    : 'Menunggu pembayaran lewat '.$metode->nama.'.',
            ]);

            // COD tidak menunggu pembayaran, melainkan konfirmasi — dan tidak
            // punya tenggat bayar sama sekali.
            $pesanan->update([
                'status' => $metode->tipe === 'cod' ? 'menunggu_konfirmasi' : 'menunggu_pembayaran',
                'batas_pembayaran' => $metode->tipe === 'cod' ? null : Carbon::now()->addHours(24),
            ]);
        });

        return redirect()->route('pesanan.show', $pesanan->no_invoice)
            ->with('success', 'Metode pembayaran diubah menjadi '.$metode->nama.'.');
    }

    public function uploadBukti(Request $request, Pesanan $pesanan)
    {
        $this->authorizeOwn($pesanan);

        abort_if($pesanan->status !== 'menunggu_pembayaran', 422, 'Pesanan tidak membutuhkan pembayaran lagi.');

        $validated = $request->validate([
            'bukti' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'nama_pengirim' => ['required', 'string', 'max:100'],
        ]);

        // Awalan "uploads/" disimpan ikut, seperti kolom gambar produk, supaya
        // asset() di tampilan menghasilkan URL yang benar.
        $file = 'uploads/'.$request->file('bukti')->store('bukti-pembayaran', 'uploads');

        DB::transaction(function () use ($pesanan, $file, $validated) {
            $pesanan->pembayaran->update([
                'bukti' => $file,
                'nama_pengirim' => $validated['nama_pengirim'],
                // Unggahan baru mengembalikan pembayaran ke antrean penilaian,
                // dan alasan penolakan sebelumnya tidak lagi berlaku.
                'status' => 'menunggu',
                'keterangan' => null,
            ]);
            $pesanan->update([
                'status' => 'menunggu_konfirmasi',
                'batas_pembayaran' => null,
            ]);
        });

        Notifikasi::keAdmin($pesanan, 'bukti_diunggah');

        return back()->with('success', 'Bukti pembayaran terkirim. Admin akan memverifikasi pesanan Anda.');
    }

    public function konfirmasiTerima(Pesanan $pesanan)
    {
        $this->authorizeOwn($pesanan);

        abort_if($pesanan->status !== 'dikirim', 422, 'Pesanan belum dikirim.');

        DB::transaction(function () use ($pesanan) {
            $pesanan->update([
                'status' => 'selesai',
                'selesai_at' => Carbon::now(),
            ]);
            $pesanan->pengiriman?->update([
                'status' => 'diterima',
                'diterima_at' => Carbon::now(),
            ]);
        });

        Notifikasi::keAdmin($pesanan, 'pesanan_diterima_pembeli');

        return back()->with('success', 'Terima kasih! Pesanan Anda telah selesai.');
    }

    public function batalkan(Pesanan $pesanan)
    {
        $this->authorizeOwn($pesanan);

        abort_if(! in_array($pesanan->status, ['menunggu_pembayaran', 'menunggu_konfirmasi']), 422,
            'Pesanan tidak dapat dibatalkan pada status ini.');

        DB::transaction(function () use ($pesanan) {
            $pesanan->kembalikanCadangan();
            $pesanan->pembayaran?->update(['status' => 'dibatalkan']);
            $pesanan->update(['status' => 'dibatalkan']);
        });

        Notifikasi::keAdmin($pesanan, 'pesanan_dibatalkan_pembeli');

        // Penanda untuk memunculkan pilihan lanjut di halaman pesanan: setelah
        // batal, tidak ada lagi yang bisa dikerjakan di sana.
        return back()
            ->with('success', 'Pesanan dibatalkan.')
            ->with('pesanan_dibatalkan', $pesanan->no_invoice);
    }

    public function cetak(string $noInvoice)
    {
        $pesanan = Pesanan::with([
            'items', 'alamat', 'pembayaran.metodePembayaran', 'pengiriman',
        ])->where('no_invoice', $noInvoice)->firstOrFail();

        if ($milikOrangLain = $this->bukanMilikSendiri($pesanan)) {
            return $milikOrangLain;
        }

        return view('pesanan.cetak', compact('pesanan'));
    }

    /**
     * Kembalikan pengalihan bila pesanan bukan milik pengguna ini.
     *
     * Halaman pesanan dibuka lewat tautan, dan tautan bisa basi — misalnya
     * tersimpan di riwayat peramban dari sesi akun lain. Menampilkan halaman
     * 403 kosong membuat pengguna buntu; mengantarnya ke daftar pesanan
     * sendiri sama amannya tetapi jauh lebih menolong.
     */
    private function bukanMilikSendiri(Pesanan $pesanan): ?RedirectResponse
    {
        if ((int) $pesanan->user_id === (int) auth()->id() || auth()->user()->isSuperadmin()) {
            return null;
        }

        return redirect()
            ->route('pesanan.index')
            ->with('error', 'Pesanan tersebut bukan milik akun Anda. Berikut daftar pesanan Anda sendiri.');
    }

    private function authorizeOwn(Pesanan $pesanan): void
    {
        abort_if((int) $pesanan->user_id !== (int) auth()->id(), 403);
    }
}