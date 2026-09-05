<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alamat_id')->constrained('alamats')->restrictOnDelete();
            $table->decimal('subtotal', 12, 0)->default(0);
            $table->decimal('ongkir', 12, 0)->default(0);
            $table->decimal('total', 12, 0)->default(0);
            $table->enum('status', [
                'menunggu_pembayaran',
                'menunggu_konfirmasi',
                'diproses',
                'dikirim',
                'selesai',
                'dibatalkan',
            ])->default('menunggu_pembayaran');
            $table->string('kurir')->nullable();
            $table->string('layanan_kurir')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('batas_pembayaran')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};