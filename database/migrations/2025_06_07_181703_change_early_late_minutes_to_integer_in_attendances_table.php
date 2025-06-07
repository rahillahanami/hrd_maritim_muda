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
        Schema::table('attendances', function (Blueprint $table) {
            // Mengubah kolom 'early_minutes' dari unsignedInteger menjadi integer
            // Penting: Pastikan doctrine/dbal sudah diinstal (composer require doctrine/dbal)
            $table->integer('early_minutes')->default(0)->change();

            // Mengubah kolom 'late_minutes' dari unsignedInteger menjadi integer
            $table->integer('late_minutes')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Mengembalikan kolom 'early_minutes' menjadi unsignedInteger
            $table->unsignedInteger('early_minutes')->default(0)->change();

            // Mengembalikan kolom 'late_minutes' menjadi unsignedInteger
            $table->unsignedInteger('late_minutes')->default(0)->change();
        });
    }
};