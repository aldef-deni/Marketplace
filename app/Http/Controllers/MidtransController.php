<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Support\Midtrans;
use App\Support\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pembayaran lewat gerbang Midtrans.
 *
 * Tiga pintu: pembeli menekan bayar, Midtrans mengabarkan hasilnya, dan
 * pembeli dapat meminta pemeriksaan ulang bila kabar itu tidak pernah sampai.
 *
 * Status pesanan hanya pernah berubah menjadi lunas lewat kabar dari Midtrans
 * yang tanda tangannya cocok — tidak pernah karena pembeli kembali ke halaman
 * selesai, yang mana siapa pun bisa membukanya sendiri.
 */
class MidtransController extends Controller
{
    /**
     * Membuat tagihan lalu melempar pembeli ke halaman pembayaran Midtrans.
     */
    public function mulai(Pesanan $pesanan)
    {
        abort_unless($pesanan->user_id === auth()->id(), 403);

        $pembayaran = $pesanan->pembayaran;

        abort_unless($pembayaran?->viaGateway(), 404);

        if (! $pembayaran->bisaDibayar()) {
            return back()->with('error', 'Pembayaran ini sudah tidak dapat dilanjutkan.');
        }

        // Tagihan yang masih hidup dipakai ulang. Membuat tagihan baru setiap
        // kali tombol ditekan akan meninggalkan deretan tagihan menggantung di
        // dasbor Midtrans untuk satu pesanan yang sama.
        if (filled($pembayaran->snap_url)) {
            return redirect()->away($pembayaran->snap_url);
        }

        try {
            $orderId = $this->orderIdBaru($pesanan);

            $tagihan = Midtrans::buatTagihan(
                $pesanan,
                $orderId,
                (array) ($pembayaran->metodePembayaran?->saluran ?? []),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pembayaran->update([
            'order_id_gateway' => $orderId,
            'snap_token' => $tagihan['token'],
            'snap_url' => $tagihan['redirect_url'],
        ]);

        return redirect()->away($tagihan['redirect_url']);
    }

    /**
     * Kabar dari Midtrans. Tanpa sesi, tanpa CSRF — pengirimnya bukan peramban.
     *
     * Balasan non-2xx membuat Midtrans mengulang kabarnya, jadi galat sementara
     * sengaja dibalas 500 agar dicoba lagi, sementara kabar yang memang tidak
     * bisa dicocokkan dibalas 200 supaya tidak diulang selamanya.
     */
    public function notifikasi(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! Midtrans::tandaTanganSah($payload)) {
            Log::warning('Notifikasi Midtrans ditolak: tanda tangan tidak cocok', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['pesan' => 'Tanda tangan tidak sah.'], 403);
        }

        $pembayaran = Pembayaran::where('order_id_gateway', $payload['order_id'] ?? '')->first();

        if (! $pembayaran) {
            Log::warning('Notifikasi Midtrans tanpa pembayaran yang cocok', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['pesan' => 'Pembayaran tidak ditemukan.']);
        }

        // Nominal wajib cocok. Tanpa ini, tagihan yang dibuat ulang dengan
        // angka lebih kecil bisa meluluskan pesanan yang lebih mahal.
        $dibayar = (float) ($payload['gross_amount'] ?? 0);

        if (abs($dibayar - (float) $pembayaran->jumlah) >= 0.01) {
            Log::error('Notifikasi Midtrans dengan nominal tidak sesuai', [
                'order_id' => $payload['order_id'] ?? null,
                'diterima' => $dibayar,
                'seharusnya' => (float) $pembayaran->jumlah,
            ]);

            return response()->json(['pesan' => 'Nominal tidak sesuai.'], 422);
        }

        $this->terapkan($pembayaran, $payload);

        return response()->json(['pesan' => 'Diterima.']);
    }

    /**
     * Pembeli meminta pemeriksaan ulang ke Midtrans.
     *
     * Webhook bisa tidak pernah sampai — tertahan firewall, atau server sedang
     * mati saat dikirim. Tanpa pintu ini, pesanan yang sudah dibayar akan
     * menggantung sampai ada yang memeriksanya secara manual.
     */
    public function periksa(Pesanan $pesanan)
    {
        abort_unless($pesanan->user_id === auth()->id(), 403);

        $pembayaran = $pesanan->pembayaran;

        abort_unless($pembayaran?->viaGateway(), 404);

        if (blank($pembayaran->order_id_gateway)) {
            return back()->with('error', 'Pembayaran ini belum pernah dimulai.');
        }

        try {
            $status = Midtrans::tanyaStatus($pembayaran->order_id_gateway);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (($status['transaction_status'] ?? '') === 'tidak_ada') {
            return back()->with('error', 'Transaksi belum tercatat di Midtrans.');
        }

        // Jawaban server-ke-server sudah terautentikasi Server Key, jadi tidak
        // perlu — dan memang tidak membawa — tanda tangan.
        $this->terapkan($pembayaran, $status);

        return back()->with('success', $pembayaran->fresh()->status === 'dibayar'
            ? 'Pembayaran Anda sudah diterima.'
            : 'Status terbaru: pembayaran belum diterima.');
    }

    /* ---------- Bagian dalam ---------- */

    /**
     * Menerapkan keadaan dari Midtrans ke pembayaran dan pesanannya.
     *
     * @param  array<string, mixed>  $payload
     */
    private function terapkan(Pembayaran $pembayaran, array $payload): void
    {
        $status = Midtrans::terjemahkanStatus(
            (string) ($payload['transaction_status'] ?? ''),
            (string) ($payload['fraud_status'] ?? 'accept'),
        );

        DB::transaction(function () use ($pembayaran, $payload, $status) {
            $pembayaran->refresh();

            // Sudah lunas tidak pernah dicabut lewat jalur ini. Midtrans mengirim
            // kabarnya berkali-kali, dan urutannya tidak dijamin — satu kabar
            // "expire" yang datang terlambat tidak boleh membatalkan pesanan
            // yang uangnya sudah masuk.
            if ($pembayaran->status === 'dibayar') {
                $pembayaran->update(['payload_gateway' => $payload]);

                return;
            }

            $pembayaran->update([
                'status' => $status,
                'kanal_gateway' => $payload['payment_type'] ?? $pembayaran->kanal_gateway,
                'payload_gateway' => $payload,
                'dibayar_at' => $status === 'dibayar' ? now() : null,
                'keterangan' => $this->keterangan($status, $payload),
            ]);

            $pesanan = $pembayaran->pesanan;

            if (! $pesanan) {
                return;
            }

            if ($status === 'dibayar' && $pesanan->status === 'menunggu_pembayaran') {
                $pesanan->update(['status' => 'diproses', 'diproses_at' => now()]);
            }

            // Tagihan kedaluwarsa atau dibatalkan: pesanannya ikut gugur, dan
            // stok yang tadi dicadangkan dikembalikan.
            if ($status === 'dibatalkan' && $pesanan->status === 'menunggu_pembayaran') {
                $this->gugurkan($pesanan);
            }
        });

        $pembayaran->refresh();

        if ($pembayaran->status === 'dibayar') {
            $pembayaran->pesanan?->load('user');
            Notifikasi::kePembeli($pembayaran->pesanan, 'pembayaran_diverifikasi');
            Notifikasi::keAdmin($pembayaran->pesanan, 'pembayaran_otomatis');
        }
    }

    private function gugurkan(Pesanan $pesanan): void
    {
        foreach ($pesanan->items as $item) {
            $item->produk?->increment('stok', $item->qty);
        }

        $pesanan->update(['status' => 'dibatalkan']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function keterangan(string $status, array $payload): string
    {
        $kanal = (string) ($payload['payment_type'] ?? 'Midtrans');
        $asli = (string) ($payload['transaction_status'] ?? '');

        return match ($status) {
            'dibayar' => 'Pembayaran diterima otomatis lewat '.$kanal.'.',
            'dibatalkan' => 'Pembayaran '.$kanal.' tidak selesai ('.$asli.').',
            default => 'Menunggu pembayaran '.$kanal.'.',
        };
    }

    /**
     * order_id unik per percobaan bayar.
     *
     * Midtrans menolak order_id yang pernah dipakai, jadi nomor invoice saja
     * tidak cukup begitu pembeli mencoba membayar untuk kedua kalinya.
     */
    private function orderIdBaru(Pesanan $pesanan): string
    {
        return $pesanan->no_invoice.'-'.Str::upper(Str::random(5));
    }
}
