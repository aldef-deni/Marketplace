@props(['teks' => 'Lanjutkan dengan Google'])

{{-- Disembunyikan bila kredensial Google belum diisi, supaya tidak ada tombol
     yang pasti gagal saat ditekan. --}}
@if (googleAktif())
    <a href="{{ route('google.redirect') }}"
       {{ $attributes->merge(['class' => 'group flex w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2']) }}>
        {{-- Lambang "G" resmi Google, empat warna. --}}
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M23.52 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.87z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.95-2.91l-3.88-3.01c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.95H1.28v3.11A11.995 11.995 0 0 0 12 24z"/>
            <path fill="#FBBC05" d="M5.27 14.28a7.2 7.2 0 0 1 0-4.56V6.61H1.28a12.01 12.01 0 0 0 0 10.78l3.99-3.11z"/>
            <path fill="#EA4335" d="M12 4.76c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.28 6.61l3.99 3.11C6.22 6.87 8.87 4.76 12 4.76z"/>
        </svg>
        <span>{{ $teks }}</span>
    </a>

    <div class="my-6 flex items-center gap-4">
        <span class="h-px flex-1 bg-slate-200"></span>
        <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">atau</span>
        <span class="h-px flex-1 bg-slate-200"></span>
    </div>
@endif
