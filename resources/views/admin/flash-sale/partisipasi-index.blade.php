<x-layouts.admin>
    <x-slot name="title">Flash Sale</x-slot>

    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
        <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent-500/25 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

        <div class="relative">
            <span class="badge bg-accent-500/15 text-accent-300 ring-accent-500/30">Program Promo</span>
            <h1 class="mt-3 text-2xl font-extrabold text-white">Flash Sale</h1>
            <p class="mt-1.5 max-w-xl text-sm text-ink-300">
                Kampanye disusun pengelola platform. Anda memutuskan apakah
                <span class="font-bold text-white">{{ $toko->nama }}</span> ikut serta,
                lalu memilih produk mana yang disertakan beserta harga dan kuotanya.
            </p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ([
            ['api', 'Sedang Berlangsung', $jumlah['berlangsung'], 'bg-emerald-50 ring-emerald-100'],
            ['jam', 'Menunggu Keputusan', $jumlah['perlu_keputusan'], 'bg-accent-50 ring-accent-100'],
            ['centang', 'Diikuti Toko', $jumlah['diikuti'], 'bg-brand-50 ring-brand-100'],
        ] as [$ikon, $label, $nilai, $warna])
            <div class="card p-5">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl ring-1 {{ $warna }}">
                    <x-ikon :nama="$ikon" kelas="h-5 w-5 text-slate-700" />
                </span>
                <p class="mt-4 text-2xl font-extrabold text-slate-900">{{ number_format($nilai) }}</p>
                <p class="text-xs font-bold text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @forelse ($kampanyes as $kampanye)
            @php ($ikut = $kampanye->tokos->contains('id', $toko->id))
            <div class="card relative overflow-hidden p-6 {{ $kampanye->sudahBerakhir() ? 'opacity-70' : '' }}">
                @if ($kampanye->sedangBerlangsung())
                    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-accent-500 to-brand-500"></span>
                @endif

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-extrabold text-slate-900">{{ $kampanye->nama }}</h3>
                            <span class="badge {{ $kampanye->status_warna }}">{{ $kampanye->status_label }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $kampanye->durasi_label }}</p>
                    </div>

                    <span class="shrink-0 rounded-xl bg-accent-50 px-3 py-1.5 text-sm font-extrabold text-accent-700 ring-1 ring-accent-200">
                        −{{ $kampanye->label_diskon }}
                    </span>
                </div>

                @if ($kampanye->deskripsi)
                    <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ $kampanye->deskripsi }}</p>
                @endif

                {{-- Keadaan keikutsertaan dibuat menonjol: inilah keputusan yang
                     diminta dari admin di halaman ini. --}}
                <div class="mt-4 flex items-center gap-3 rounded-2xl p-4 ring-1
                            {{ $ikut ? 'bg-emerald-50 ring-emerald-200' : 'bg-amber-50 ring-amber-200' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                 {{ $ikut ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        <x-ikon :nama="$ikut ? 'centang' : 'jam'" kelas="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold {{ $ikut ? 'text-emerald-800' : 'text-amber-800' }}">
                            {{ $ikut ? 'Toko mengikuti kampanye ini' : 'Toko belum mengikuti kampanye ini' }}
                        </p>
                        <p class="mt-0.5 text-xs {{ $ikut ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $ikut
                                ? $kampanye->produks_count.' produk toko ini disertakan'
                                : 'Harga flash belum berlaku sampai Anda ikut serta' }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    @unless ($kampanye->sudahBerakhir())
                        <form method="POST" action="{{ route('admin.flash-sale.ikut', $kampanye) }}">
                            @csrf
                            <button class="{{ $ikut ? 'btn-secondary' : 'btn-primary' }} btn-sm">
                                {{ $ikut ? 'Berhenti Ikut' : 'Ikuti Kampanye' }}
                            </button>
                        </form>
                    @endunless

                    <a href="{{ route('admin.flash-sale.kelola', $kampanye) }}" class="btn-secondary btn-sm">
                        {{ $kampanye->sudahBerakhir() ? 'Lihat Produk' : 'Kelola Produk' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="lg:col-span-2">
                <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 p-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-accent-100 text-accent-700">
                        <x-ikon nama="api" kelas="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-600">Belum ada kampanye yang diterbitkan</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Kampanye flash sale disusun oleh superadmin. Halaman ini akan terisi
                        begitu ada kampanye yang diterbitkan.
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($kampanyes->hasPages())
        <div class="mt-6">{{ $kampanyes->links() }}</div>
    @endif
</x-layouts.admin>
