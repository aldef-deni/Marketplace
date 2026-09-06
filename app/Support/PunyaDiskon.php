<?php

namespace App\Support;

/**
 * Potongan harga berbentuk persentase atau nominal rupiah.
 *
 * Dipakai bersama oleh kampanye flash sale dan promo. Aturannya ditaruh di satu
 * tempat karena keduanya harus menghasilkan angka yang sama persis — harga yang
 * dipajang di katalog wajib sama dengan yang ditagih saat checkout.
 *
 * Model pemakainya wajib punya kolom `tipe_diskon` ('persen'|'nominal') dan
 * `nilai_diskon`.
 */
trait PunyaDiskon
{
    public function persen(): bool
    {
        return $this->tipe_diskon === 'persen';
    }

    /**
     * Besar potongan dalam rupiah untuk sebuah harga normal.
     *
     * Tidak pernah melebihi harga normalnya sendiri: potongan nominal yang
     * lebih besar dari harganya akan menghasilkan harga negatif, dan pesanan
     * bernilai minus jauh lebih berbahaya daripada promo yang tidak menarik.
     */
    public function potongan(float $hargaNormal): float
    {
        if ($hargaNormal <= 0 || $this->nilai_diskon <= 0) {
            return 0.0;
        }

        $potongan = $this->persen()
            ? $hargaNormal * ((float) $this->nilai_diskon / 100)
            : (float) $this->nilai_diskon;

        return min($potongan, $hargaNormal);
    }

    /**
     * Harga sesudah potongan, dibulatkan ke rupiah penuh.
     */
    public function hargaSetelahPotongan(float $hargaNormal): float
    {
        return max(0.0, round($hargaNormal - $this->potongan($hargaNormal)));
    }

    public function persenHemat(float $hargaNormal): int
    {
        if ($hargaNormal <= 0) {
            return 0;
        }

        return (int) round(($this->potongan($hargaNormal) / $hargaNormal) * 100);
    }

    /**
     * Label singkat potongan, misalnya "20%" atau "Rp 15.000".
     */
    public function getLabelDiskonAttribute(): string
    {
        return $this->persen()
            ? $this->nilai_diskon.'%'
            : rp($this->nilai_diskon);
    }

    public function getTipeDiskonLabelAttribute(): string
    {
        return $this->persen() ? 'Persentase' : 'Nominal';
    }
}
