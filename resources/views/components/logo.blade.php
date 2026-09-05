@props([
    'varian' => 'landscape',   // landscape | portrait | ikon
    'kelas'  => 'h-9 w-auto',
])

@php
    /*
     | Setiap varian punya pasangan WebP (ringan) dan PNG (fallback).
     | Ukuran berkas dipilih sesuai pemakaian agar tidak memuat logo 1600px
     | hanya untuk lencana 36px di bilah navigasi.
     */
    $berkas = match ($varian) {
        'portrait' => ['images/logo-portrait-520', config('brand.nama') . ' — logo'],
        'ikon'     => ['images/icon-192', config('brand.nama') . ' — ikon'],
        default    => ['images/logo-landscape-160', config('brand.nama')],
    };

    [$dasar, $alt] = $berkas;
@endphp

<picture>
    <source srcset="{{ asset($dasar . '.webp') }}" type="image/webp">
    <img src="{{ asset($dasar . '.png') }}"
         alt="{{ $alt }}"
         loading="{{ $attributes->get('loading', 'eager') }}"
         decoding="async"
         {{ $attributes->except('loading')->merge(['class' => $kelas]) }}>
</picture>
