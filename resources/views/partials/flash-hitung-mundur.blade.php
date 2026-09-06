{{-- Hitung mundur kampanye: hari, jam, menit, detik.

     Dimulai dari sisa detik yang dihitung server, supaya jam perangkat pembeli
     yang meleset tidak ikut menggeser angkanya. Dipakai beranda dan halaman
     flash sale, karena keduanya harus menunjukkan angka yang sama persis.

     Variabel: $flashSale, opsional $terang (untuk latar gelap) dan $label. --}}
@php
    $terang = $terang ?? false;
    $label = $label ?? 'Berakhir dalam';
@endphp

<div x-data="{
        sisa: {{ $flashSale->sisaDetik() }},
        angka(n) { return String(Math.floor(n)).padStart(2, '0') },
        get bagian() {
            return [
                ['Hari', this.angka(this.sisa / 86400)],
                ['Jam', this.angka((this.sisa % 86400) / 3600)],
                ['Menit', this.angka((this.sisa % 3600) / 60)],
                ['Detik', this.angka(this.sisa % 60)],
            ]
        },
        mulai() { setInterval(() => { if (this.sisa > 0) this.sisa-- }, 1000) },
     }"
     x-init="mulai()"
     class="shrink-0">
    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider {{ $terang ? 'text-ink-400' : 'text-slate-500' }}">
        {{ $label }}
    </p>

    <div class="flex items-start gap-1.5 sm:gap-2">
        <template x-for="(b, i) in bagian" :key="b[0]">
            <div class="flex items-start gap-1.5 sm:gap-2">
                <div class="w-14 rounded-2xl px-1 py-2 text-center shadow-sm backdrop-blur sm:w-16
                            {{ $terang ? 'bg-white/10 ring-1 ring-white/20' : 'bg-white/70 ring-1 ring-white/80' }}">
                    <span class="block text-xl font-extrabold tabular-nums sm:text-2xl {{ $terang ? 'text-white' : 'text-slate-900' }}"
                          x-text="b[1]"></span>
                    <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-wide {{ $terang ? 'text-ink-400' : 'text-slate-500' }}"
                          x-text="b[0]"></span>
                </div>
                <span x-show="i < 3" class="pt-2 text-lg font-extrabold text-accent-500 sm:text-xl">:</span>
            </div>
        </template>
    </div>
</div>
