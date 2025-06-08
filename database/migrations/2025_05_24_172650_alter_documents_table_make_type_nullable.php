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
            $table->string('type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Untuk rollback: Mengembalikan kolom 'type' menjadi not nullable.
        // HATI-HATI: Jika ada data di kolom 'type' yang bernilai NULL,
        // migrasi ini akan gagal saat rollback (php artisan migrate:rollback).
        // Anda mungkin perlu memberikan nilai default sementara pada data yang NULL terlebih dahulu
        // atau menghapus data yang NULL sebelum rollback, tergantung kebutuhan.
        Schema::table('documents', function (Blueprint $table) {
            $table->string('type')->nullable(false)->change();
        });
    }
};
