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
    Schema::table('work_plans', function (Blueprint $table) {
        // Pastikan doctrine/dbal sudah diinstal (composer require doctrine/dbal)
        $table->foreignId('user_id')->nullable()->change();
    });
}
public function down(): void
{
    Schema::table('work_plans', function (Blueprint $table) {
        // HATI-HATI: Ini bisa gagal jika ada data null.
        $table->foreignId('user_id')->nullable(false)->change();
    });
}
};
