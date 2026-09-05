{{-- Meta dasar, ikon, dan kartu berbagi. Dipakai oleh seluruh layout. --}}
@php
    $metaJudul = ($judul ?? '') !== ''
        ? $judul . ' — ' . config('brand.nama')
        : config('brand.nama') . ' — ' . config('brand.tagline');
    $metaDeskripsi = $deskripsi ?? config('brand.deskripsi');
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $metaJudul }}</title>
<meta name="description" content="{{ $metaDeskripsi }}">
<meta name="theme-color" content="{{ config('brand.warna.gelap') }}">
<link rel="canonical" href="{{ url()->current() }}">

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset(config('brand.logo.ikon_192')) }}">
<link rel="apple-touch-icon" href="{{ asset(config('brand.logo.ikon_180')) }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('brand.nama') }}">
<meta property="og:title" content="{{ $metaJudul }}">
<meta property="og:description" content="{{ $metaDeskripsi }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset(config('brand.logo.og')) }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaJudul }}">
<meta name="twitter:description" content="{{ $metaDeskripsi }}">
<meta name="twitter:image" content="{{ asset(config('brand.logo.og')) }}">

<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
