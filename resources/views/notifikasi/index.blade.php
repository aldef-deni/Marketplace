@php
    // Admin dan superadmin membaca notifikasi di dalam panelnya sendiri,
    // pembeli di layout toko, supaya navigasinya tidak berpindah konteks.
    // Judul halaman ditaruh di badan, bukan slot header, karena layout admin
    // tidak memiliki slot tersebut dan isinya akan hilang tanpa jejak.
    $layout = auth()->user()->isPengelola() ? 'layouts.admin' : 'layouts.app';
    $belumDibaca = auth()->user()->unreadNotifications()->count();
    $sudahDibaca = auth()->user()->readNotifications()->count();
@endphp

<x-dynamic-component :component="$layout">
    <x-slot name="title">Notifikasi</x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Notifikasi</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $belumDibaca }} belum dibaca dari {{ $notifikasis->total() }} notifikasi.
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if ($belumDibaca > 0)
                    <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                        @csrf
                        <button class="btn-secondary btn-sm">Tandai semua terbaca</button>
                    </form>
                @endif

                @if ($sudahDibaca > 0)
                    <form method="POST" action="{{ route('notifikasi.hapus-terbaca') }}"
                          onsubmit="return confirm('Hapus semua notifikasi yang sudah dibaca?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn-secondary btn-sm !text-rose-600">Bersihkan yang terbaca</button>
                    </form>
                @endif
            </div>
        </div>

        @forelse ($notifikasis as $notifikasi)
            @php
                $d = $notifikasi->data;
                $nada = match ($d['nada'] ?? 'brand') {
                    'accent' => 'bg-accent-100 text-accent-700',
                    'emerald' => 'bg-emerald-100 text-emerald-700',
                    'rose' => 'bg-rose-100 text-rose-700',
                    default => 'bg-brand-100 text-brand-700',
                };
            @endphp

            <a href="{{ route('notifikasi.baca', $notifikasi->id) }}"
               class="mb-3 flex gap-4 rounded-2xl p-4 shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md
                      {{ $notifikasi->read_at ? 'bg-white ring-slate-200/70' : 'bg-brand-50/50 ring-brand-200' }}">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $nada }}">
                    <x-ikon :nama="$d['ikon'] ?? 'info'" kelas="h-5 w-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-bold text-slate-800">{{ $d['judul'] ?? 'Notifikasi' }}</p>
                        @unless ($notifikasi->read_at)
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-accent-500"></span>
                        @endunless
                    </div>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $d['pesan'] ?? '' }}</p>
                    <p class="mt-1.5 text-xs text-slate-400">
                        {{ $notifikasi->created_at->diffForHumans() }}
                        @if (! empty($d['invoice']))
                            <span class="mx-1 text-slate-300">&bull;</span>{{ $d['invoice'] }}
                        @endif
                    </p>
                </div>
            </a>
        @empty
            <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 p-14 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <x-ikon nama="info" kelas="h-6 w-6" />
                </span>
                <p class="mt-4 text-sm font-semibold text-slate-600">Belum ada notifikasi</p>
                <p class="mt-1 text-xs text-slate-400">
                    Pemberitahuan tentang pesanan dan pembayaran akan muncul di sini.
                </p>
            </div>
        @endforelse

        @if ($notifikasis->hasPages())
            <div class="mt-6">{{ $notifikasis->links() }}</div>
        @endif
    </div>
</x-dynamic-component>
