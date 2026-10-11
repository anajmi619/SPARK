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
        Schema::create('rollover_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years', 'academic_year_id')->restrictOnDelete();
            $table->foreignId('previous_year_id')->nullable()->constrained('academic_years', 'academic_year_id')->restrictOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users', 'user_id')->restrictOnDelete();
            $table->enum('event', ['scheduled', 'cancelled', 'completed', 'failed']);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->unsignedInteger('activated_count')->nullable();
            $table->unsignedInteger('deactivated_count')->nullable();
            $table->string('error_type')->nullable();
            $table->unsignedBigInteger('completed_year')->nullable()
                ->storedAs("CASE WHEN event = 'completed' THEN academic_year_id ELSE NULL END")->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rollover_audits');
    }
};
