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
        Schema::table('events', function (Blueprint $table) {
            // Pastikan doctrine/dbal sudah diinstal (composer require doctrine/dbal)
            $table->string('created_by')->nullable()->change(); // Asumsi created_by adalah string
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Mengembalikan ke not nullable (sesuai definisi awal)
            $table->string('created_by')->nullable(false)->change();
        });
    }
};