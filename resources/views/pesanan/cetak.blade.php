<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $pesanan->no_invoice }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; padding: 40px; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #0B5FB0; padding-bottom: 20px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .logo { height: 46px; width: auto; }
        .brand-teks p { font-size: 11px; color: #94a3b8; line-height: 1.5; }
        .title { text-align: right; }
        .title h2 { font-size: 16px; text-transform: uppercase; letter-spacing: 1px; color: #0B5FB0; }
        .title p { font-size: 12px; color: #64748b; margin-top: 4px; }
        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 24px 0; }
        .box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
        .box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 8px; }
        .box p { font-size: 13px; line-height: 1.6; }
        .box .nama { font-weight: 700; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #EEF6FF; color: #084B8E; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; padding: 10px 12px; }
        td { font-size: 13px; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        .total-wrap { display: flex; justify-content: flex-end; margin-top: 20px; }
        .total { width: 300px; }
        .total div { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .total .grand { border-top: 2px solid #0B5FB0; margin-top: 6px; padding-top: 10px; font-size: 16px; font-weight: 800; color: #0B5FB0; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 11px; color: #94a3b8; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .status { background: #EEF6FF; color: #084B8E; }
        @media print { body { padding: 20px; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <img class="logo" src="{{ asset(config('brand.logo.landscape_160')) }}" alt="{{ config('brand.nama') }}">
            <div class="brand-teks">
                <p>{{ config('brand.alamat') }} &bull; {{ config('brand.email') }}</p>
                <p>{{ config('brand.domain') }}</p>
            </div>
        </div>
        <div class="title">
            <h2>Invoice</h2>
            <p>{{ $pesanan->no_invoice }}</p>
            <p>{{ tanggalIndo($pesanan->created_at, true) }}</p>
            <span class="badge status">{{ $pesanan->status_label }}</span>
        </div>
    </div>

    <div class="info">
        <div class="box">
            <h3>Tagihan Kepada</h3>
            <p class="nama">{{ $pesanan->user->name }}</p>
            <p>{{ $pesanan->user->email }}</p>
            <p>{{ $pesanan->user->phone }}</p>
        </div>
        <div class="box">
            <h3>Dikirim Ke</h3>
            <p class="nama">{{ $pesanan->alamat->nama_penerima }}</p>
            <p>{{ $pesanan->alamat->no_hp }}</p>
            <p>{{ $pesanan->alamat->alamat_lengkap_koma }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th>Harga</th>
                <th style="text-align:center">Qty</th>
                <th style="text-align:right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pesanan->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nama_produk }}</td>
                    <td>{{ rp($item->harga) }}</td>
                    <td style="text-align:center">{{ $item->qty }}</td>
                    <td style="text-align:right">{{ rp($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-wrap">
        <div class="total">
            <div><span>Subtotal</span><span>{{ rp($pesanan->subtotal) }}</span></div>
            <div><span>Ongkos Kirim ({{ $pesanan->kurir }} {{ $pesanan->layanan_kurir }})</span><span>{{ rp($pesanan->ongkir) }}</span></div>
            <div class="grand"><span>Total</span><span>{{ rp($pesanan->total) }}</span></div>
        </div>
    </div>

    <div class="footer">
        Terima kasih telah berbelanja di {{ config('brand.nama') }} &bull; Invoice ini sah dan diproses otomatis oleh sistem.
    </div>

    <script>window.print();</script>
</body>
</html>