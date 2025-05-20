<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('leave_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pegawai_id')->constrained('pegawai')->onDelete('cascade');
        $table->enum('leave_type', ['annual', 'sick', 'personal', 'other']);
        $table->date('start_date');
        $table->date('end_date');
        $table->text('reason');
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->text('rejection_reason')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
