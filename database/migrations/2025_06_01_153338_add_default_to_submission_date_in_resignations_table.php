<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Impor Facade DB

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resignations', function (Blueprint $table) {
            // Mengubah tipe kolom menjadi timestamp dan menambahkan default CURRENT_TIMESTAMP
            // Ini adalah cara paling andal untuk mengubah kolom dengan default fungsi MySQL.
            DB::statement('ALTER TABLE resignations CHANGE submission_date submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resignations', function (Blueprint $table) {
            // Mengembalikan tipe kolom ke DATE dan menghapus default.
            // Anda perlu menyesuaikan ini dengan kondisi asli kolom jika itu not nullable.
            // Jika aslinya 'date' dan tidak ada default:
            DB::statement('ALTER TABLE resignations CHANGE submission_date submission_date DATE DEFAULT NULL');
        });
    }
};