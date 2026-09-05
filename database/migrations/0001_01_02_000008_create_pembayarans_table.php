<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanans')->cascadeOnDelete();
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayarans')->restrictOnDelete();
            $table->string('kode')->unique();
            $table->decimal('jumlah', 12, 0);
            $table->enum('status', ['menunggu', 'dibayar', 'dibatalkan'])->default('menunggu');
            $table->string('bukti')->nullable();
            $table->string('nama_pengirim')->nullable();
            $table->timestamp('dibayar_at')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['pesanan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};