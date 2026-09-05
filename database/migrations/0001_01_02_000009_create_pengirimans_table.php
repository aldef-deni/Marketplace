<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengirimans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanans')->cascadeOnDelete();
            $table->string('kurir');
            $table->string('layanan');
            $table->string('no_resi')->nullable();
            $table->decimal('ongkir', 12, 0)->default(0);
            $table->enum('status', ['menunggu', 'dikirim', 'diterima'])->default('menunggu');
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamp('diterima_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('no_resi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengirimans');
    }
};