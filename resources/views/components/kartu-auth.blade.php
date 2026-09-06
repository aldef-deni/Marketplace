@props(['judul', 'keterangan' => null])

{{-- Kartu tunggal untuk halaman autentikasi yang isinya pendek — lupa sandi,
     atur ulang sandi, dan konfirmasi sandi.

     Halaman masuk dan daftar memakai kartu dua panel karena ada ruang untuk
     menjual merek; ketiga halaman ini datang di tengah alur yang mendesak,
     jadi yang dibutuhkan justru satu kolom tanpa gangguan. --}}
<div class="mx-auto flex min-h-[70vh] max-w-lg items-center justify-center px-4 py-12 sm:px-6">
    <div class="w-full rounded-3xl bg-white p-8 shadow-2xl shadow-brand-100 ring-1 ring-slate-200/70 sm:p-10">
        <div class="mb-8 flex justify-center">
            <a href="{{ route('beranda') }}" class="inline-flex transition hover:opacity-90"
               aria-label="{{ config('brand.nama') }} — beranda">
                <x-logo varian="landscape" kelas="h-14 w-auto sm:h-16" />
            </a>
        </div>

        <h1 class="text-2xl font-extrabold text-slate-900">{{ $judul }}</h1>

        @if ($keterangan)
            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $keterangan }}</p>
        @endif

        <div class="mt-8">{{ $slot }}</div>
    </div>
</div>
