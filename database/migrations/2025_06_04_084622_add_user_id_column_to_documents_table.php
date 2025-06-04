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
            // Tambahkan kolom user_id sebagai foreign key
            // Pastikan doctrine/dbal terinstal
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete()->after('status');

            // Opsional: Drop uploaded_by jika masih ada (karena harusnya sudah dihapus oleh migrasi sebelumnya)
            // if (Schema::hasColumn('documents', 'uploaded_by')) {
            //     $table->dropColumn('uploaded_by');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Hapus user_id saat rollback
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('user_id'); // Menghapus kolom itu sendiri

            // Opsional: Kembalikan uploaded_by saat rollback jika Anda membutuhkannya kembali
            // $table->string('uploaded_by')->nullable()->after('status');
        });
    }
};