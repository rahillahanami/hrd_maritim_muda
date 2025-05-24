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
        Schema::create('documents', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->string('category');
             $table->string('type'); // PDF, DOCX, etc
             $table->string('file_path'); // Path ke file
             $table->string('uploaded_by');
             $table->date('uploaded_at');
             $table->enum('status', ['Draft', 'Published']);
             $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete();
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
