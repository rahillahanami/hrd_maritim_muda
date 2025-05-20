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
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->enum('kelamin', ['pria', 'wanita']);
            $table->date('tanggal_lahir');
            $table->string('nomor_hp', 20);
            $table->text('alamat');
            $table->foreignId('divisi_id')
                  ->constrained('divisi', 'id')
                  ->restrictOnDelete(); 
           $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
