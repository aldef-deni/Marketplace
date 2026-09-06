<?php

namespace App\Models;

use App\Support\PotonganBerlaku;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    protected $fillable = [
        'toko_id', 'kategori_id', 'nama', 'slug', 'deskripsi', 'harga', 'harga_coret',
        'stok', 'berat', 'gambar', 'status',
    ];

    protected $casts = [
        'toko_id' => 'integer',
        'kategori_id' => 'integer',
        'harga' => 'decimal:0',
        'harga_coret' => 'decimal:0',
        'stok' => 'integer',
        'berat' => 'integer',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }

    public function flashSaleItems(): HasMany
    {
        return $this->hasMany(FlashSaleProduk::class);
    }

    public function promoItems(): HasMany
    {
        return $this->hasMany(PromoProduk::class);
    }

    /**
     * Baris flash sale yang harganya berlaku untuk produk ini sekarang.
     *
     * Tiga syarat: kampanyenya berjalan, toko pemilik produk ikut serta, dan
     * kuotanya belum habis. Syarat kedua penting sejak katalog dimiliki banyak
     * lapak — satu kampanye kini bisa diikuti sebagian toko saja.
     *
     * Hasilnya di-cache pada instance karena satu halaman katalog memanggilnya
     * sekali per produk, dan tanpa itu setiap kartu memicu kuerinya sendiri.
     */
    public function flashSaleBerlaku(): ?FlashSaleProduk
    {
        if (array_key_exists('flashSaleBerlaku', $this->relations)) {
            return $this->relations['flashSaleBerlaku'];
        }

        $baris = $this->toko_id === null ? null : $this->flashSaleItems()
            ->with('flashSale')
            ->whereHas('flashSale', fn ($q) => $q->berlangsung()
                ->whereHas('tokos', fn ($t) => $t->where('tokos.id', $this->toko_id)))
            ->get()
            ->first(fn (FlashSaleProduk $b) => ! $b->kuotaHabis());

        $this->relations['flashSaleBerlaku'] = $baris;

        return $baris;
    }

    /**
     * Baris promo yang berlaku untuk produk ini sekarang.
     *
     * Promo milik tokonya sendiri langsung berlaku; promo platform baru berlaku
     * setelah tokonya ikut serta.
     */
    public function promoBerlaku(): ?PromoProduk
    {
        if (array_key_exists('promoBerlaku', $this->relations)) {
            return $this->relations['promoBerlaku'];
        }

        $tokoId = $this->toko_id;

        $baris = $tokoId === null ? null : $this->promoItems()
            ->with('promo')
            ->whereHas('promo', fn ($q) => $q->berlangsung()
                ->where(fn ($p) => $p->where('toko_id', $tokoId)
                    ->orWhere(fn ($pp) => $pp->whereNull('toko_id')
                        ->whereHas('tokos', fn ($t) => $t->where('tokos.id', $tokoId)))))
            ->get()
            ->first(fn (PromoProduk $b) => ! $b->kuotaHabis());

        $this->relations['promoBerlaku'] = $baris;

        return $baris;
    }

    /**
     * Potongan yang benar-benar dipakai untuk produk ini saat ini.
     *
     * Bila flash sale dan promo sama-sama berlaku, yang menghasilkan harga
     * terendah yang menang — memajang yang lebih mahal padahal ada yang lebih
     * murah akan terbaca sebagai menahan diskon.
     */
    public function potonganBerlaku(): ?PotonganBerlaku
    {
        if (array_key_exists('potonganBerlaku', $this->relations)) {
            return $this->relations['potonganBerlaku'];
        }

        $normal = (float) $this->harga;
        $kandidat = [];

        if ($flash = $this->flashSaleBerlaku()) {
            $kandidat[] = new PotonganBerlaku(
                jenis: 'flash',
                label: 'Flash Sale',
                harga: (float) $flash->harga_flash,
                hargaNormal: $normal,
                persenHemat: $flash->persen_hemat,
                sisaKuota: $flash->sisaKuota(),
                sumber: $flash,
            );
        }

        if ($promo = $this->promoBerlaku()) {
            $kandidat[] = new PotonganBerlaku(
                jenis: 'promo',
                label: $promo->promo?->nama ?? 'Promo',
                harga: $promo->hargaPromo(),
                hargaNormal: $normal,
                persenHemat: $promo->persen_hemat,
                sisaKuota: $promo->sisaKuota(),
                sumber: $promo,
            );
        }

        $terbaik = PotonganBerlaku::terbaik(...$kandidat);

        $this->relations['potonganBerlaku'] = $terbaik;

        return $terbaik;
    }

    public function sedangFlashSale(): bool
    {
        return $this->potonganBerlaku()?->flashSale() ?? false;
    }

    public function sedangDipotong(): bool
    {
        return $this->potonganBerlaku() !== null;
    }

    /**
     * Harga yang benar-benar dibayar pembeli saat ini.
     *
     * Seluruh tampilan dan perhitungan memakai satu sumber ini, supaya harga di
     * katalog, keranjang, dan checkout tidak mungkin berbeda.
     */
    public function hargaEfektif(): float
    {
        return $this->potonganBerlaku()?->harga ?? (float) $this->harga;
    }

    /**
     * Harga sebelum potongan, untuk dicoret di tampilan.
     */
    public function hargaSebelumPotongan(): ?float
    {
        if ($this->sedangDipotong()) {
            return (float) $this->harga;
        }

        return $this->harga_coret && $this->harga_coret > $this->harga
            ? (float) $this->harga_coret
            : null;
    }

    public function keranjangItems(): HasMany
    {
        return $this->hasMany(Keranjang::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('stok', '>', 0);
    }

    public function getDiskonPersenAttribute(): ?int
    {
        if ($this->harga_coret && $this->harga_coret > $this->harga) {
            return (int) round((($this->harga_coret - $this->harga) / $this->harga_coret) * 100);
        }

        return null;
    }
}