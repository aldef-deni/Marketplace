{{-- Ringkasan notifikasi untuk dashboard. Dipakai peran pembeli maupun admin. --}}
<div class="card p-6">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Notifikasi Terbaru</h3>
        <a href="{{ route('notifikasi.index') }}" class="text-xs font-bold text-brand-600 transition hover:text-brand-800">
            Lihat semua &rarr;
        </a>
    </div>

    <div class="mt-4 space-y-2">
        @forelse ($notifikasiTerbaru as $notifikasi)
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
               class="flex items-start gap-3 rounded-2xl p-3 transition hover:bg-slate-50 {{ $notifikasi->read_at ? '' : 'bg-brand-50/50' }}">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $nada }}">
                    <x-ikon :nama="$d['ikon'] ?? 'info'" kelas="h-4 w-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-slate-800">{{ $d['judul'] ?? 'Notifikasi' }}</p>
                    <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $d['pesan'] ?? '' }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ $notifikasi->created_at->diffForHumans() }}</p>
                </div>
                @unless ($notifikasi->read_at)
                    <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-accent-500"></span>
                @endunless
            </a>
        @empty
            <p class="py-8 text-center text-sm text-slate-400">Belum ada notifikasi.</p>
        @endforelse
    </div>
</div>
