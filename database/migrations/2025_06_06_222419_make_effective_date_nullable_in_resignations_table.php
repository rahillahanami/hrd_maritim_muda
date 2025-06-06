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
            // Mengubah kolom 'effective_date' menjadi nullable
            $table->date('effective_date')->nullable()->change();

            // *** TAMBAHKAN BARIS INI UNTUK 'reason' ***
            // Mengubah kolom 'reason' menjadi nullable
            $table->text('reason')->nullable()->change(); // Asumsi 'reason' adalah text
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resignations', function (Blueprint $table) {
            // Mengembalikan kolom 'effective_date' menjadi not nullable
            $table->date('effective_date')->nullable(false)->change();

            // *** TAMBAHKAN BARIS INI UNTUK 'reason' ***
            // Mengembalikan kolom 'reason' menjadi not nullable
            $table->text('reason')->nullable(false)->change(); // Asumsi 'reason' adalah text
        });
    }
};