<x-layouts.admin>
    <x-slot name="title">Toko</x-slot>

    @php ($pengelola = auth()->user()->isSuperadmin())

    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
        <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent-500/25 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-5">
            <div>
                <span class="badge bg-accent-500/15 text-accent-300 ring-accent-500/30">
                    {{ $pengelola ? 'Pengelola Platform' : 'Toko Saya' }}
                </span>
                <h1 class="mt-3 text-2xl font-extrabold text-white">{{ $pengelola ? 'Daftar Toko' : 'Toko Saya' }}</h1>
                <p class="mt-1.5 max-w-lg text-sm text-ink-300">
                    {{ $pengelola
                        ? 'Setujui pendaftaran toko baru, tangguhkan yang bermasalah, dan pantau isinya.'
                        : 'Kelola identitas toko Anda. Toko yang aktif tampil di etalase pembeli.' }}
                </p>
            </div>

            @if ($pengelola)
                <a href="{{ route('admin.toko.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-5 py-3 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                    <x-ikon nama="tambah" kelas="h-4 w-4" />
                    Buat Toko
                </a>
            @endif
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['toko', 'Total Toko', $jumlah['semua'], 'bg-brand-50 ring-brand-100'],
            ['centang', 'Aktif', $jumlah['aktif'], 'bg-emerald-50 ring-emerald-100'],
            ['jam', 'Menunggu Persetujuan', $jumlah['menunggu'], 'bg-accent-50 ring-accent-100'],
            ['silang', 'Ditangguhkan', $jumlah['nonaktif'], 'bg-slate-50 ring-slate-100'],
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

    @if ($pengelola)
        <form method="GET" class="card mt-6 flex flex-wrap items-center gap-3 p-4">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama toko atau kota…"
                   class="input-field !w-64 !py-2.5 text-sm">

            <select name="status" class="input-field !w-auto !py-2.5 text-sm">
                <option value="">Semua status</option>
                @foreach (\App\Models\Toko::STATUS as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn-primary !py-2.5">Terapkan</button>

            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route('admin.toko.index') }}" class="btn-ghost !py-2.5">Reset</a>
            @endif
        </form>
    @endif

    <div class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Toko</th>
                        <th class="table-head">Pemilik</th>
                        <th class="table-head">Lokasi</th>
                        <th class="table-head text-center">Produk</th>
                        <th class="table-head text-center">Status</th>
                        <th class="table-head text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tokos as $toko)
                        <tr>
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                        @if ($toko->logo)
                                            <img src="{{ asset($toko->logo) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-600 to-accent-500 text-xs font-extrabold text-white">
                                                {{ $toko->inisial }}
                                            </span>
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800">{{ $toko->nama }}</p>
                                        <p class="truncate text-xs text-slate-400">/{{ $toko->slug }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="table-cell">
                                <p class="font-semibold text-slate-700">{{ $toko->pemilik?->name ?? '—' }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $toko->pemilik?->email }}</p>
                            </td>

                            <td class="table-cell text-slate-600">{{ $toko->lokasi ?? '—' }}</td>

                            <td class="table-cell text-center font-bold text-slate-700">{{ $toko->produks_count }}</td>

                            <td class="table-cell text-center">
                                <span class="badge {{ $toko->status_warna }}">{{ $toko->status_label }}</span>
                            </td>

                            <td class="table-cell">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($toko->aktif())
                                        <a href="{{ route('toko.show', $toko->slug) }}" target="_blank" rel="noopener"
                                           class="btn-secondary btn-sm">Lihat</a>
                                    @endif

                                    <a href="{{ route('admin.toko.edit', $toko) }}" class="btn-secondary btn-sm">Edit</a>

                                    @if ($pengelola)
                                        <form method="POST" action="{{ route('admin.toko.status', $toko) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn-secondary btn-sm {{ $toko->aktif() ? '!text-amber-600' : '!text-emerald-600' }}">
                                                {{ $toko->aktif() ? 'Tangguhkan' : ($toko->status === 'menunggu' ? 'Setujui' : 'Aktifkan') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-14 text-center">
                                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                                    <x-ikon nama="toko" kelas="h-6 w-6" />
                                </span>
                                <p class="mt-4 text-sm font-semibold text-slate-600">
                                    {{ $pengelola ? 'Belum ada toko terdaftar' : 'Anda belum punya toko' }}
                                </p>
                                @if ($pengelola)
                                    <a href="{{ route('admin.toko.create') }}" class="btn-primary mt-5">Buat Toko Pertama</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($tokos->hasPages())
        <div class="mt-6">{{ $tokos->links() }}</div>
    @endif
</x-layouts.admin>
