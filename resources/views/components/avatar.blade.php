@props([
    'user',
    'ukuran' => 'h-9 w-9',
    'teks' => 'text-xs',
    'cincin' => 'ring-1 ring-white/15',
])

{{-- Menampilkan foto bila ada, dan inisial berlatar gradasi merek bila tidak.
     Dipakai di bilah navigasi, panel admin, dashboard, dan halaman profil agar
     wajah pengguna tampil seragam di seluruh aplikasi. --}}
@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}"
         alt="Foto profil {{ $user->name }}"
         loading="lazy" decoding="async"
         referrerpolicy="no-referrer"
         {{ $attributes->merge(['class' => "$ukuran shrink-0 rounded-full object-cover $cincin"]) }}>
@else
    <span {{ $attributes->merge([
        'class' => "$ukuran $teks shrink-0 select-none rounded-full bg-gradient-to-br from-brand-600 to-accent-500 font-bold text-white $cincin inline-flex items-center justify-center",
    ]) }}>{{ initials($user->name) }}</span>
@endif
