<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resignations', function (Blueprint $table) {
            // Pastikan doctrine/dbal sudah diinstal (composer require doctrine/dbal)
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // *** UBAH BAGIAN INI ***
        Schema::table('resignations', function (Blueprint $table) { // <<< TAMBAHKAN BARIS INI
            // HATI-HATI: Ini bisa menyebabkan error jika ada data NULL di kolom ini saat rollback
            // dan kolom tidak boleh NULL
            $table->foreignId('user_id')->nullable(false)->change();
        }); // <<< TAMBAHKAN BARIS INI
        // *** AKHIR PERUBAHAN ***
    }
};