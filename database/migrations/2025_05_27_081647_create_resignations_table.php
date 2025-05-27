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
        Schema::create('resignations', function (Blueprint $table) {
            $table->id();

            // Foreign key ke tabel 'users' untuk karyawan yang mengajukan resign
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Jika user dihapus, pengajuan resignnya ikut terhapus

            $table->date('submission_date'); // Tanggal pengajuan dibuat
            $table->date('effective_date');  // Tanggal efektif resign (tanggal terakhir bekerja)

            $table->text('reason'); // Alasan mengapa mengajukan resign

            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled'])->default('Pending'); // Status pengajuan resign

            $table->text('notes')->nullable(); // Catatan internal dari HR/manajer

            // Foreign key ke tabel 'users' untuk user yang menyetujui/menolak (opsional)
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resignations');
    }
};