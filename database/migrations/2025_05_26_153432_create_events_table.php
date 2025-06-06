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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable(); // Deskripsi acara
            $table->string('created_by');
            $table->foreignId('division_id')->nullable()->constrained('divisions');
            $table->dateTime('starts_at'); // Waktu mulai acara
            $table->dateTime('ends_at'); // Waktu selesai acara
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
