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
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('type'); // Menghapus kolom 'file_path'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Mengembalikan kolom 'file_path' saat rollback
            // Pastikan tipe data dan propertinya sesuai dengan definisi asli Anda
            // Berdasarkan code awal, file_path adalah string
            $table->string('type')->nullable(); // Asumsi aslinya string dan nullable
        });
    }
};