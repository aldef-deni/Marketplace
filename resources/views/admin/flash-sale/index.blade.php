<x-layouts.admin>
    <x-slot name="title">Kampanye Flash Sale</x-slot>

    {{-- Kepala --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
        <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent-500/25 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-5">
            <div>
                <span class="badge bg-accent-500/15 text-accent-300 ring-accent-500/30">Superadmin</span>
                <h1 class="mt-3 text-2xl font-extrabold text-white">Kampanye Flash Sale</h1>
                <p class="mt-1.5 max-w-lg text-sm text-ink-300">
                    Susun kampanye beserta jadwalnya. Setelah diterbitkan, admin toko dapat
                    mengikutinya dan memilih produk yang disertakan.
                </p>
            </div>

            <a href="{{ route('admin.flash-sale.kampanye.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-5 py-3 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                <x-ikon nama="tambah" kelas="h-4 w-4" />
                Buat Kampanye
            </a>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['api', 'Berlangsung', $jumlah['berlangsung'], 'bg-emerald-50 ring-emerald-100'],
            ['jam', 'Terjadwal', $jumlah['terjadwal'], 'bg-brand-50 ring-brand-100'],
            ['pensil', 'Draf', $jumlah['draf'], 'bg-slate-50 ring-slate-100'],
            ['papan', 'Total Kampanye', $jumlah['semua'], 'bg-accent-50 ring-accent-100'],
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

    {{-- Daftar --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @forelse ($kampanyes as $kampanye)
            <div class="card relative overflow-hidden p-6">
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
                        −{{ $kampanye->diskon_persen }}%
                    </span>
                </div>

                @if ($kampanye->deskripsi)
                    <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ $kampanye->deskripsi }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <x-ikon nama="label" kelas="h-4 w-4" />
                        {{ $kampanye->produks_count }} produk disertakan
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-ikon :nama="$kampanye->diikuti ? 'centang' : 'jam'" kelas="h-4 w-4" />
                        {{ $kampanye->diikuti ? 'Diikuti toko' : 'Belum diikuti toko' }}
                    </span>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    <form method="POST" action="{{ route('admin.flash-sale.kampanye.terbit', $kampanye) }}">
                        @csrf
                        @method('PATCH')
                        <button class="{{ $kampanye->aktif ? 'btn-secondary' : 'btn-primary' }} btn-sm">
                            {{ $kampanye->aktif ? 'Tarik Penerbitan' : 'Terbitkan' }}
                        </button>
                    </form>

                    <a href="{{ route('admin.flash-sale.kampanye.edit', $kampanye) }}" class="btn-secondary btn-sm">Edit</a>

                    @if ($kampanye->aktif)
                        <a href="{{ route('admin.flash-sale.kelola', $kampanye) }}" class="btn-secondary btn-sm">Lihat Produk</a>
                    @endif

                    <form method="POST" action="{{ route('admin.flash-sale.kampanye.destroy', $kampanye) }}"
                          class="ml-auto"
                          onsubmit="return confirm('Hapus kampanye {{ $kampanye->nama }}? Produk yang disertakan ikut terlepas.')">
                        @csrf
                        @method('DELETE')
                        <button class="btn-secondary btn-sm !text-rose-600">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="lg:col-span-2">
                <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 p-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-accent-100 text-accent-700">
                        <x-ikon nama="api" kelas="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-600">Belum ada kampanye flash sale</p>
                    <p class="mt-1 text-xs text-slate-400">Buat kampanye pertama untuk mulai menjadwalkan promo kilat.</p>
                    <a href="{{ route('admin.flash-sale.kampanye.create') }}" class="btn-primary mt-6">Buat Kampanye</a>
                </div>
            </div>
        @endforelse
    </div>

    @if ($kampanyes->hasPages())
        <div class="mt-6">{{ $kampanyes->links() }}</div>
    @endif
</x-layouts.admin>
