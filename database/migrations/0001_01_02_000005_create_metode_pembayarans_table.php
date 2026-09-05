<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metode_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('tipe', ['transfer', 'ewallet', 'cod'])->default('transfer');
            $table->string('nomor_rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->string('logo')->nullable();
            $table->text('instruksi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metode_pembayarans');
    }
};