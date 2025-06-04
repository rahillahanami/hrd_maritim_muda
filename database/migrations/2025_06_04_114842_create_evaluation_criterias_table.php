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
        Schema::create('evaluation_criterias', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->decimal('weight', 5, 2); // contoh: 0.25
        $table->enum('type', ['benefit', 'cost']);
        $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_criterias');
    }
};
