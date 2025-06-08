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
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // 1. Drop the 'user_id' foreign key
            $table->dropConstrainedForeignId('user_id');

            // 2. Drop the 'user_id' column
            $table->dropColumn('user_id');

            // 3. Re-add the original 'uploaded_by' column (kembalikan seperti semula)
            $table->string('uploaded_by')->nullable()->after('status'); // Asumsi aslinya string dan nullable
        });
    }
};
