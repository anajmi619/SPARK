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
        Schema::table('academic_years', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_slot')->nullable()->storedAs("CASE WHEN status = 'active' THEN 1 ELSE NULL END")->unique();
            $table->foreignId('scheduled_by')->nullable()->constrained('users', 'user_id')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scheduled_by');
            $table->dropUnique(['active_slot']);
            $table->dropColumn('active_slot');
        });
    }
};
