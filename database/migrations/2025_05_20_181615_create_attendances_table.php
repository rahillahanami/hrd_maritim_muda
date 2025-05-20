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
        Schema::create('attendances', function (Blueprint $table) {
             $table->id();
             $table->foreignId(column: 'pegawai_id')->constrained('pegawai', 'id')->cascadeOnDelete();
             $table->date('date');
             $table->timestamp('check_in')->nullable();
             $table->timestamp('check_out')->nullable();
             $table->enum('status', ['present', 'absent', 'late'])->default('present');
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
