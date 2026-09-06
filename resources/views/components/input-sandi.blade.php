@props(['kelas' => 'input-field', 'pembungkus' => ''])

{{-- Kolom kata sandi dengan tombol lihat/sembunyi.

     Jenis kolomnya tetap ditulis "password" pada HTML mentah dan baru
     ditimpa Alpine setelah ditekan. Jadi sebelum skripnya sempat berjalan
     — atau bila gagal dimuat sama sekali — sandi tetap tersamar, bukan
     malah telanjang di layar.

     Tombolnya tidak dikeluarkan dari urutan tab: pengguna papan ketik pun
     berhak memeriksa apa yang sudah diketiknya. --}}
<div class="relative {{ $pembungkus }}" x-data="{ tampil: false }">
    <input type="password" :type="tampil ? 'text' : 'password'"
           {{ $attributes->merge(['class' => $kelas.' pr-11']) }}>

    <button type="button" @click="tampil = ! tampil"
            :aria-label="tampil ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
            :aria-pressed="tampil ? 'true' : 'false'"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-brand-600 focus:text-brand-600 focus:outline-none">
        <span x-show="! tampil"><x-ikon nama="mata" kelas="h-5 w-5" /></span>
        <span x-show="tampil" x-cloak><x-ikon nama="mata-tutup" kelas="h-5 w-5" /></span>
    </button>
</div>
