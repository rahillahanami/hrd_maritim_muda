<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Untuk DB::statement jika perlu

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Mengubah kolom 'uploaded_at' menjadi DATETIME
            // Penting: Pastikan doctrine/dbal sudah diinstal (composer require doctrine/dbal)
            // Jika kolomnya sudah ada data DATE, MySQL akan mengkonversinya ke DATETIME dengan 00:00:00.
            $table->dateTime('uploaded_at')->change();

            // Opsional: Jika Anda ingin juga menyertakan DEFAULT CURRENT_TIMESTAMP di level database,
            // Anda mungkin perlu DB::statement() setelah ini, atau saat membuat tabel.
            // Contoh: DB::statement('ALTER TABLE documents CHANGE uploaded_at uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Mengembalikan kolom ke tipe DATE saat rollback
            $table->date('uploaded_at')->change();
        });
    }
};