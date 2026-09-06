{{-- Kartu satu toko. Tautannya dibentangkan sebagai lapisan absolut agar
     tombol atau tautan lain di dalam kartu tetap bisa diklik sendiri. --}}
<div class="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-100 hover:ring-brand-200">
    <a href="{{ route('toko.show', $toko->slug) }}" class="absolute inset-0 z-10">
        <span class="sr-only">Kunjungi {{ $toko->nama }}</span>
    </a>

    {{-- Sampul --}}
    <div class="relative h-24 shrink-0 overflow-hidden bg-gradient-to-br from-brand-800 via-brand-600 to-accent-500 sm:h-28">
        @if ($toko->banner)
            <img src="{{ asset($toko->banner) }}" alt="" draggable="false"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
            <div class="pola-grid absolute inset-0 opacity-70"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>

        <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm backdrop-blur">
            <x-ikon nama="perisai" kelas="h-3 w-3" />
            Terverifikasi
        </span>
    </div>

    <div class="flex flex-1 flex-col px-4 pb-4">
        {{-- Logo ditumpangkan di batas sampul. Wadahnya wajib "relative":
             sampul di atasnya juga berposisi, dan elemen berposisi selalu
             dilukis di atas yang statis — logonya akan tertimbun tanpa ini. --}}
        <div class="relative -mt-9 mb-1">
            <span class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-200">
                @if ($toko->logo)
                    <img src="{{ asset($toko->logo) }}" alt="{{ $toko->nama }}" draggable="false"
                         class="h-full w-full object-cover" loading="lazy">
                @else
                    <span class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-600 to-accent-500 text-lg font-extrabold text-white">
                        {{ $toko->inisial }}
                    </span>
                @endif
            </span>
        </div>

        <p class="mt-2 truncate text-sm font-extrabold text-slate-900 transition group-hover:text-brand-700">
            {{ $toko->nama }}
        </p>

        <p class="mt-1 flex items-center gap-1 text-xs font-medium text-slate-400">
            <x-ikon nama="lokasi" kelas="h-3.5 w-3.5 shrink-0" />
            <span class="truncate">{{ $toko->lokasi ?? 'Lokasi belum diisi' }}</span>
        </p>

        @if ($toko->deskripsi)
            <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $toko->deskripsi }}</p>
        @endif

        {{-- mt-auto menahan baris ini di dasar kartu, supaya deskripsi satu
             baris dan dua baris tetap sejajar dalam satu deret. --}}
        <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3">
            <span class="text-xs font-bold text-slate-600">
                {{ number_format($toko->produks_count ?? 0) }} produk
            </span>
            <span class="text-xs font-bold text-brand-600 transition group-hover:text-accent-600">
                Kunjungi &rarr;
            </span>
        </div>
    </div>
</div>
