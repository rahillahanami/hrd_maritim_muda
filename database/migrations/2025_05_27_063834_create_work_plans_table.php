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
        Schema::create('work_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Judul atau nama rencana kerja
            $table->text('description')->nullable(); // Detail atau deskripsi rencana

            // Kolom opsional untuk target kuantitatif
            $table->string('target_metric')->nullable(); // Misal: "Jumlah Penjualan", "Laporan Terselesaikan"
            $table->decimal('target_value', 8, 2)->nullable(); // Misal: 15.00 (untuk 15%), 5.00 (untuk 5 laporan)

            $table->date('start_date'); // Tanggal mulai rencana
            $table->date('due_date');   // Tenggat waktu penyelesaian rencana

            $table->integer('progress_percentage')->default(0); // Progres dalam persentase (0-100)

            $table->enum('status', ['Draft', 'On Progress', 'Completed', 'Pending Review', 'Cancelled'])->default('Draft'); // Status rencana kerja

            // Foreign key ke tabel 'users' untuk pemilik rencana kerja (karyawan/user)
            // Diasumsikan tabel 'users' sudah ada
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Jika user dihapus, rencana kerjanya ikut terhapus

            // Foreign key ke tabel 'divisions' (opsional)
            // Diasumsikan tabel 'divisions' sudah ada
            $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete();

            $table->text('notes')->nullable(); // Catatan tambahan atau update progress

            // Foreign key ke tabel 'users' untuk user yang menyetujui (opsional)
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Jika user yang menyetujui dihapus, nilai ini jadi NULL

            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_plans');
    }
};
