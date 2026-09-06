<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu potongan harga yang benar-benar berlaku untuk sebuah produk saat ini.
 *
 * Flash sale dan promo punya bentuk data yang berbeda, tetapi tampilan hanya
 * perlu tahu tiga hal: berapa harganya, berapa hematnya, dan sisa jatahnya.
 * Objek kecil ini menyatukan keduanya supaya kartu produk tidak perlu bercabang
 * mengurus dua jenis sumber.
 */
final class PotonganBerlaku
{
    public function __construct(
        public readonly string $jenis,
        public readonly string $label,
        public readonly float $harga,
        public readonly float $hargaNormal,
        public readonly int $persenHemat,
        public readonly ?int $sisaKuota,
        public readonly Model $sumber,
    ) {}

    public function flashSale(): bool
    {
        return $this->jenis === 'flash';
    }

    public function hemat(): float
    {
        return max(0, $this->hargaNormal - $this->harga);
    }

    /**
     * Potongan yang paling menguntungkan pembeli di antara beberapa pilihan.
     *
     * Aturannya sengaja "harga terendah menang", bukan urutan jenis tertentu:
     * memajang promo yang lebih mahal padahal ada yang lebih murah akan terbaca
     * sebagai menahan diskon. Saat harganya sama, flash sale didahulukan karena
     * jatah dan tenggatnya perlu ikut terpakai.
     */
    public static function terbaik(?self ...$kandidat): ?self
    {
        $ada = array_values(array_filter($kandidat));

        if ($ada === []) {
            return null;
        }

        usort($ada, function (self $a, self $b) {
            return $a->harga <=> $b->harga
                ?: ($b->flashSale() <=> $a->flashSale());
        });

        return $ada[0];
    }
}
