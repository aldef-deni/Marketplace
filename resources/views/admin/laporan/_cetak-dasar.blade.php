{{-- Kerangka cetak PDF.
     Gaya ditulis inline dan sederhana karena dompdf hanya mendukung sebagian
     kecil CSS — flexbox, grid, dan variabel CSS tidak dikenalinya. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('judul') — {{ config('brand.nama') }}</title>
    <style>
        @page { margin: 22px 26px; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1B2231;
            margin: 0;
        }

        .kepala { border-bottom: 3px solid #0B5FB0; padding-bottom: 10px; margin-bottom: 12px; }
        .kepala td { vertical-align: top; border: 0; padding: 0; }
        .merek { font-size: 16px; font-weight: bold; color: #0B5FB0; }
        .merek span { color: #F59300; }
        .sub { color: #6F8099; font-size: 8px; margin-top: 2px; }
        .judul { font-size: 13px; font-weight: bold; text-align: right; }
        .waktu { text-align: right; color: #6F8099; font-size: 8px; margin-top: 3px; }

        .kriteria { background: #F5F7FA; border: 1px solid #E9EDF3; padding: 7px 9px; margin-bottom: 12px; }
        .kriteria span { display: inline-block; margin-right: 16px; }
        .kriteria b { color: #374357; }

        h2 {
            font-size: 10px; text-transform: uppercase; letter-spacing: .6px;
            color: #4C5B70; margin: 14px 0 6px; padding-bottom: 3px;
            border-bottom: 1px solid #E9EDF3;
        }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: #0B5FB0; color: #fff; font-size: 8px; text-align: left;
            padding: 5px 6px; text-transform: uppercase; letter-spacing: .4px;
        }
        table.data td { padding: 4px 6px; border-bottom: 1px solid #E9EDF3; }
        table.data tr:nth-child(even) td { background: #F8FAFC; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .tebal { font-weight: bold; }

        .kartu { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 4px; }
        .kartu td {
            background: #F5F7FA; border: 1px solid #E9EDF3; padding: 8px 10px; width: 25%;
        }
        .kartu .nilai { font-size: 13px; font-weight: bold; color: #0A3D72; }
        .kartu .label { font-size: 8px; color: #6F8099; text-transform: uppercase; letter-spacing: .4px; }

        .kaki {
            position: fixed; bottom: -6px; left: 0; right: 0;
            color: #A6B3C6; font-size: 7px; text-align: center;
        }
        .kosong { text-align: center; color: #A6B3C6; padding: 16px; }
    </style>
</head>
<body>

<table class="kepala" width="100%">
    <tr>
        <td>
            <div class="merek">Market <span>ArahInn</span></div>
            <div class="sub">{{ config('brand.domain') }} &bull; {{ config('brand.email') }}</div>
        </td>
        <td>
            <div class="judul">@yield('judul')</div>
            <div class="waktu">Dibuat {{ now()->translatedFormat('d F Y H:i') }} oleh {{ auth()->user()->name }}</div>
        </td>
    </tr>
</table>

<div class="kriteria">
    @foreach ($filter->ringkasanKriteria() as $label => $nilai)
        <span><b>{{ $label }}:</b> {{ $nilai }}</span>
    @endforeach
</div>

@yield('isi')

<div class="kaki">
    {{ config('brand.nama') }} &bull; Laporan ini dihasilkan otomatis oleh sistem
</div>

</body>
</html>
