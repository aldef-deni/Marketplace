<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\ProdukGambar;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Galeri gambar produk beserta pemilihan gambar bawaannya.
 *
 * Yang dijaga di sini: produks.gambar tidak pernah menunjuk baris yang sudah
 * dibuang, tidak pernah kosong selama masih ada gambar tersisa, dan pemilik
 * toko tidak bisa menyentuh gambar lapak lain.
 */
class ProdukGambarTest extends TestCase
{
    use RefreshDatabase;

    private User $pemilikA;

    private User $pemilikB;

    private Toko $tokoA;

    private Toko $tokoB;

    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads');

        $this->pemilikA = User::factory()->create(['role' => 'admin']);
        $this->pemilikB = User::factory()->create(['role' => 'admin']);

        $this->tokoA = $this->buatToko($this->pemilikA, 'Lapak Alfa');
        $this->tokoB = $this->buatToko($this->pemilikB, 'Lapak Beta');

        $this->kategori = Kategori::create(['nama' => 'Elektronik', 'slug' => 'elektronik']);
    }

    private function buatToko(User $pemilik, string $nama): Toko
    {
        return Toko::create([
            'user_id' => $pemilik->id,
            'nama' => $nama,
            'slug' => \Illuminate\Support\Str::slug($nama),
            'status' => 'aktif',
            'disetujui_at' => now(),
        ]);
    }

    private function buatProduk(Toko $toko, string $nama = 'Produk Uji'): Produk
    {
        return Produk::create([
            'toko_id' => $toko->id,
            'kategori_id' => $this->kategori->id,
            'nama' => $nama,
            'slug' => \Illuminate\Support\Str::slug($nama),
            'harga' => 50000,
            'stok' => 5,
            'berat' => 500,
            'status' => 'aktif',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(array $tambahan = []): array
    {
        return array_merge([
            'toko_id' => $this->tokoA->id,
            'kategori_id' => $this->kategori->id,
            'nama' => 'Produk Uji',
            'harga' => 50000,
            'stok' => 5,
            'berat' => 500,
            'status' => 'aktif',
        ], $tambahan);
    }

    private function berkas(string $nama): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 600, 600);
    }

    /* ---------- Unggah ---------- */

    public function test_beberapa_gambar_tersimpan_sekaligus(): void
    {
        $this->actingAs($this->pemilikA)
            ->post(route('admin.produk.store'), $this->isian([
                'gambar' => [$this->berkas('satu.jpg'), $this->berkas('dua.jpg'), $this->berkas('tiga.jpg')],
            ]))
            ->assertSessionHasNoErrors();

        $produk = Produk::firstWhere('nama', 'Produk Uji');

        $this->assertCount(3, $produk->gambars);
        $this->assertSame([1, 2, 3], $produk->gambars->pluck('urutan')->all());
    }

    public function test_jalur_gambar_berawalan_uploads_agar_tidak_berujung_404(): void
    {
        $this->actingAs($this->pemilikA)
            ->post(route('admin.produk.store'), $this->isian(['gambar' => [$this->berkas('satu.jpg')]]));

        $produk = Produk::firstWhere('nama', 'Produk Uji');

        $this->assertStringStartsWith('uploads/produk/', $produk->gambar);
        $this->assertStringStartsWith('uploads/produk/', $produk->gambars->first()->jalur);
    }

    public function test_tanpa_pilihan_gambar_pertama_menjadi_bawaan(): void
    {
        $this->actingAs($this->pemilikA)
            ->post(route('admin.produk.store'), $this->isian([
                'gambar' => [$this->berkas('satu.jpg'), $this->berkas('dua.jpg')],
            ]));

        $produk = Produk::firstWhere('nama', 'Produk Uji');

        $this->assertSame($produk->gambars->first()->jalur, $produk->gambar);
    }

    public function test_gambar_bawaan_dapat_dipilih_dari_unggahan_baru(): void
    {
        $this->actingAs($this->pemilikA)
            ->post(route('admin.produk.store'), $this->isian([
                'gambar' => [$this->berkas('satu.jpg'), $this->berkas('dua.jpg'), $this->berkas('tiga.jpg')],
                'gambar_utama' => 'baru:2',
            ]));

        $produk = Produk::firstWhere('nama', 'Produk Uji');

        $this->assertSame($produk->gambars->last()->jalur, $produk->gambar);
    }

    public function test_lebih_dari_delapan_gambar_ditolak(): void
    {
        $this->actingAs($this->pemilikA)
            ->post(route('admin.produk.store'), $this->isian([
                'gambar' => array_map(fn ($i) => $this->berkas("g{$i}.jpg"), range(1, 9)),
            ]))
            ->assertSessionHasErrors('gambar');

        $this->assertDatabaseCount('produk_gambars', 0);
    }

    public function test_berkas_yang_bukan_gambar_ditolak(): void
    {
        $this->actingAs($this->pemilikA)
            ->post(route('admin.produk.store'), $this->isian([
                'gambar' => [UploadedFile::fake()->create('daftar.pdf', 100, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('gambar.0');
    }

    /* ---------- Sunting ---------- */

    public function test_gambar_baru_ditambahkan_tanpa_menghapus_yang_lama(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $lama = $produk->gambars()->create(['jalur' => 'uploads/produk/lama.jpg', 'urutan' => 1]);
        $produk->update(['gambar' => $lama->jalur]);

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $produk), $this->isian([
                'gambar' => [$this->berkas('baru.jpg')],
            ]))
            ->assertSessionHasNoErrors();

        $produk->refresh()->load('gambars');

        $this->assertCount(2, $produk->gambars);
        // Bawaan yang lama tetap bertahan; menambah gambar bukan menggantinya.
        $this->assertSame('uploads/produk/lama.jpg', $produk->gambar);
    }

    public function test_gambar_bawaan_dapat_dipindah_ke_gambar_lama_lainnya(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $satu = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $dua = $produk->gambars()->create(['jalur' => 'uploads/produk/dua.jpg', 'urutan' => 2]);
        $produk->update(['gambar' => $satu->jalur]);

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $produk), $this->isian([
                'gambar_utama' => 'lama:'.$dua->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('uploads/produk/dua.jpg', $produk->fresh()->gambar);
    }

    public function test_gambar_yang_dibuang_dilepas_dari_galeri(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $satu = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $dua = $produk->gambars()->create(['jalur' => 'uploads/produk/dua.jpg', 'urutan' => 2]);
        $produk->update(['gambar' => $satu->jalur]);

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $produk), $this->isian([
                'buang_gambar' => [$dua->id],
            ]));

        $this->assertDatabaseMissing('produk_gambars', ['id' => $dua->id]);
        $this->assertSame('uploads/produk/satu.jpg', $produk->fresh()->gambar);
    }

    public function test_membuang_gambar_bawaan_mengalihkan_bawaan_ke_yang_tersisa(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $satu = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $produk->gambars()->create(['jalur' => 'uploads/produk/dua.jpg', 'urutan' => 2]);
        $produk->update(['gambar' => $satu->jalur]);

        // Bawaannya dibuang tanpa menyebut pengganti: kolom gambar tidak boleh
        // ditinggalkan menunjuk baris yang sudah hilang.
        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $produk), $this->isian([
                'buang_gambar' => [$satu->id],
            ]));

        $this->assertSame('uploads/produk/dua.jpg', $produk->fresh()->gambar);
    }

    public function test_membuang_seluruh_gambar_mengosongkan_kolom_bawaan(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $satu = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $dua = $produk->gambars()->create(['jalur' => 'uploads/produk/dua.jpg', 'urutan' => 2]);
        $produk->update(['gambar' => $satu->jalur]);

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $produk), $this->isian([
                'buang_gambar' => [$satu->id, $dua->id],
            ]));

        $this->assertNull($produk->fresh()->gambar);
    }

    public function test_membuang_dan_mengunggah_dalam_satu_kiriman(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $satu = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $produk->update(['gambar' => $satu->jalur]);

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $produk), $this->isian([
                'buang_gambar' => [$satu->id],
                'gambar' => [$this->berkas('pengganti.jpg')],
                'gambar_utama' => 'baru:0',
            ]))
            ->assertSessionHasNoErrors();

        $produk->refresh()->load('gambars');

        $this->assertCount(1, $produk->gambars);
        $this->assertSame($produk->gambars->first()->jalur, $produk->gambar);
    }

    /* ---------- Batas antar-toko ---------- */

    public function test_pemilik_toko_tidak_dapat_membuang_gambar_lapak_lain(): void
    {
        $milikB = $this->buatProduk($this->tokoB, 'Produk Beta');
        $gambarB = $milikB->gambars()->create(['jalur' => 'uploads/produk/beta.jpg', 'urutan' => 1]);
        $milikB->update(['gambar' => $gambarB->jalur]);

        $milikA = $this->buatProduk($this->tokoA);
        $gambarA = $milikA->gambars()->create(['jalur' => 'uploads/produk/alfa.jpg', 'urutan' => 1]);
        $milikA->update(['gambar' => $gambarA->jalur]);

        // Id gambar milik lapak lain diselipkan ke formulir produknya sendiri.
        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $milikA), $this->isian([
                'buang_gambar' => [$gambarB->id],
            ]));

        $this->assertDatabaseHas('produk_gambars', ['id' => $gambarB->id]);
        $this->assertSame('uploads/produk/beta.jpg', $milikB->fresh()->gambar);
    }

    public function test_pemilik_toko_tidak_dapat_mengunggah_ke_produk_lapak_lain(): void
    {
        $milikB = $this->buatProduk($this->tokoB, 'Produk Beta');

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.produk.update', $milikB), $this->isian([
                'gambar' => [$this->berkas('sisipan.jpg')],
            ]))
            ->assertForbidden();

        $this->assertDatabaseCount('produk_gambars', 0);
    }

    /* ---------- Tampilan ---------- */

    public function test_galeri_menempatkan_gambar_bawaan_di_depan(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $dua = $produk->gambars()->create(['jalur' => 'uploads/produk/dua.jpg', 'urutan' => 2]);
        $produk->update(['gambar' => $dua->jalur]);

        $galeri = $produk->fresh()->load('gambars')->galeri();

        $this->assertSame(
            ['uploads/produk/dua.jpg', 'uploads/produk/satu.jpg'],
            $galeri->all(),
        );
    }

    public function test_halaman_produk_menampilkan_seluruh_gambarnya(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $satu = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $produk->gambars()->create(['jalur' => 'uploads/produk/dua.jpg', 'urutan' => 2]);
        $produk->update(['gambar' => $satu->jalur]);

        $this->get(route('produk.show', $produk->slug))
            ->assertOk()
            ->assertSee('uploads/produk/satu.jpg', false)
            ->assertSee('uploads/produk/dua.jpg', false);
    }

    public function test_menghapus_produk_ikut_membersihkan_galerinya(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);

        $this->actingAs($this->pemilikA)->delete(route('admin.produk.destroy', $produk));

        $this->assertDatabaseCount('produk_gambars', 0);
    }

    public function test_gambar_lama_terbawa_ke_galeri_saat_membuka_formulir(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $gambar = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $produk->update(['gambar' => $gambar->jalur]);

        $this->actingAs($this->pemilikA)
            ->get(route('admin.produk.edit', $produk))
            ->assertOk()
            ->assertSee('Galeri Gambar Produk')
            ->assertSee('lama:'.$gambar->id, false);
    }

    public function test_gambar_dianggap_utama_hanya_bila_jalurnya_cocok(): void
    {
        $produk = $this->buatProduk($this->tokoA);
        $satu = $produk->gambars()->create(['jalur' => 'uploads/produk/satu.jpg', 'urutan' => 1]);
        $dua = $produk->gambars()->create(['jalur' => 'uploads/produk/dua.jpg', 'urutan' => 2]);
        $produk->update(['gambar' => $satu->jalur]);

        $this->assertTrue(ProdukGambar::find($satu->id)->utama());
        $this->assertFalse(ProdukGambar::find($dua->id)->utama());
    }
}
