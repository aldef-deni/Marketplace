@props(['gelap' => true])

@php
    // Lonceng dipakai di dua permukaan: bilah gelap (toko & dashboard) dan
    // bilah terang (panel admin), jadi warnanya ditentukan lewat prop.
    $tombol = $gelap
        ? 'text-ink-300 hover:bg-white/5 hover:text-white'
        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800';
@endphp

<div x-data="lonceng()" x-init="mulai()" class="relative">
    <button type="button" @click="buka = ! buka; if (buka) muat()"
            class="relative rounded-lg p-2.5 transition {{ $tombol }}"
            :aria-expanded="buka" aria-label="Notifikasi">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
        </svg>

        <span x-show="jumlah > 0" x-cloak
              class="absolute right-0.5 top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-bold leading-none text-ink-950"
              x-text="jumlah > 99 ? '99+' : jumlah"></span>
    </button>

    <div x-show="buka" x-cloak @click.outside="buka = false" x-transition
         class="absolute right-0 z-50 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-elevate">

        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-extrabold text-slate-800">Notifikasi</p>
            <form method="POST" action="{{ route('notifikasi.baca-semua') }}" x-show="jumlah > 0">
                @csrf
                <button class="text-xs font-bold text-brand-600 transition hover:text-brand-800">Tandai terbaca</button>
            </form>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <template x-if="daftar.length === 0">
                <p class="px-4 py-10 text-center text-sm text-slate-400">Belum ada notifikasi.</p>
            </template>

            <template x-for="n in daftar" :key="n.id">
                <a :href="'{{ url('notifikasi') }}/' + n.id + '/baca'"
                   class="flex gap-3 border-b border-slate-50 px-4 py-3 transition hover:bg-slate-50"
                   :class="! n.dibaca && 'bg-brand-50/40'">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                          :class="{
                              'bg-brand-100 text-brand-700': n.nada === 'brand',
                              'bg-accent-100 text-accent-700': n.nada === 'accent',
                              'bg-emerald-100 text-emerald-700': n.nada === 'emerald',
                              'bg-rose-100 text-rose-700': n.nada === 'rose',
                          }">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="8"/>
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-800" x-text="n.judul"></p>
                        <p class="mt-0.5 text-xs leading-relaxed text-slate-500" x-text="n.pesan"></p>
                        <p class="mt-1 text-[11px] text-slate-400" x-text="n.waktu"></p>
                    </div>
                    <span x-show="! n.dibaca" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-accent-500"></span>
                </a>
            </template>
        </div>

        <a href="{{ route('notifikasi.index') }}"
           class="block border-t border-slate-100 px-4 py-3 text-center text-xs font-bold text-brand-600 transition hover:bg-slate-50">
            Lihat semua notifikasi
        </a>
    </div>
</div>

@once
    @push('skrip')
        <script>
            function lonceng() {
                return {
                    buka: false,
                    jumlah: 0,
                    daftar: [],

                    mulai() {
                        this.muat();

                        // Hosting yang dipakai tidak menjalankan proses daemon,
                        // jadi pembaruan langsung ditempuh lewat polling ringan.
                        // Interval dihentikan saat tab tidak terlihat agar tidak
                        // memanggil server sia-sia.
                        setInterval(() => {
                            if (! document.hidden) this.muat();
                        }, 30000);

                        document.addEventListener('visibilitychange', () => {
                            if (! document.hidden) this.muat();
                        });
                    },

                    async muat() {
                        try {
                            const r = await fetch('{{ route('notifikasi.data') }}', {
                                headers: { 'Accept': 'application/json' },
                                credentials: 'same-origin',
                            });
                            if (! r.ok) return;
                            const d = await r.json();
                            this.jumlah = d.belum_dibaca;
                            this.daftar = d.daftar;
                        } catch (e) {
                            // Jaringan putus sesaat bukan alasan untuk merusak halaman.
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
