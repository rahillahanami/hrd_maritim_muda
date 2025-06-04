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
        Schema::create('employee_scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
        $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
        $table->foreignId('evaluation_criteria_id')->constrained()->cascadeOnDelete();
        $table->decimal('score', 5, 2); // contoh: 75.5
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_scores');
    }
};
