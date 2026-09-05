<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identitas akun Google. Disimpan agar penautan tetap benar walau
            // pengguna kelak mengganti alamat surelnya di Google.
            $table->string('google_id')->nullable()->unique()->after('email_verified_at');
        });

        // Akun yang mendaftar lewat Google tidak punya kata sandi sama sekali.
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Kata sandi dikembalikan wajib, jadi akun tanpa sandi harus dibereskan
        // lebih dulu agar kolomnya tidak menolak nilai null yang tersisa.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
