{{-- Pemilih lapak untuk pengelola platform.

     Penjual hanya punya satu toko sehingga pemilihnya tidak berguna baginya —
     yang ditampilkan cukup namanya, supaya tetap jelas lapak mana yang sedang
     diubah. Pilihan disimpan di sesi, jadi tombol-tombol di halaman ini tidak
     perlu ikut membawa parameternya. --}}
@php ($banyak = ($tokos ?? collect())->count() > 1)

<div class="flex flex-wrap items-center gap-2 rounded-2xl bg-white/[0.07] p-2 pl-3.5 ring-1 ring-white/10 backdrop-blur">
    <span class="text-[11px] font-bold uppercase tracking-wider text-ink-400">Atas nama</span>

    @if ($banyak)
        <form method="GET" class="flex items-center gap-2">
            <select name="toko" onchange="this.form.submit()"
                    class="rounded-xl border-white/10 bg-ink-900 py-1.5 pl-3 pr-8 text-sm font-bold text-white focus:border-accent-500/60 focus:ring-accent-500/50">
                @foreach ($tokos as $pilihan)
                    <option value="{{ $pilihan->slug }}" @selected($pilihan->id === $toko->id)>{{ $pilihan->nama }}</option>
                @endforeach
            </select>
            <noscript><button class="btn-secondary btn-sm">Ganti</button></noscript>
        </form>
    @else
        <span class="rounded-xl bg-white/10 px-3 py-1.5 text-sm font-bold text-white">{{ $toko->nama }}</span>
    @endif
</div>
