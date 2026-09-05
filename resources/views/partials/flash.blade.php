{{-- Notifikasi sesi: sukses, gagal, dan informasi. --}}
@php
    $pesan = collect([
        'success' => ['warna' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'ikon' => 'M4.5 12.75l6 6 9-13.5'],
        'error'   => ['warna' => 'border-rose-200 bg-rose-50 text-rose-800',          'ikon' => 'M6 18L18 6M6 6l12 12'],
        'info'    => ['warna' => 'border-brand-200 bg-brand-50 text-brand-800',       'ikon' => 'M11.25 11.25h1.5v5.25h-1.5zM12 7.5h.01'],
    ])->filter(fn ($_, $kunci) => session()->has($kunci));
@endphp

@if ($pesan->isNotEmpty())
    <div class="mx-auto mt-5 max-w-7xl space-y-2 px-4 sm:px-6 lg:px-8">
        @foreach ($pesan as $kunci => $gaya)
            <div x-data="{ tampil: true }" x-show="tampil" x-transition
                 class="flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm font-medium shadow-sm {{ $gaya['warna'] }}">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $gaya['ikon'] }}"/>
                </svg>
                <p class="flex-1">{{ session($kunci) }}</p>
                <button type="button" @click="tampil = false" class="shrink-0 opacity-50 transition hover:opacity-100" aria-label="Tutup">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
