<?php

use App\Enums\AttemptStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id('attempt_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedSmallInteger('topic_id');
            $table->unsignedSmallInteger('attempt_number');
            $table->unsignedTinyInteger('score')->nullable();
            $table->enum('status', array_column(AttemptStatus::cases(), 'value'))->default(AttemptStatus::InProgress->value);
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('topics')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unique(['student_id', 'topic_id', 'attempt_number']);
            $table->index(['student_id', 'topic_id', 'status', 'score']);
            $table->index(['topic_id', 'status', 'completed_at']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE quiz_attempts ADD CONSTRAINT chk_quiz_attempts_number CHECK (attempt_number >= 1)');
            DB::statement(<<<'SQL'
                ALTER TABLE quiz_attempts ADD CONSTRAINT chk_quiz_attempts_state CHECK (
                    (status = 'in_progress' AND score IS NULL AND completed_at IS NULL)
                    OR
                    (status = 'completed' AND score BETWEEN 0 AND 10 AND completed_at IS NOT NULL)
                )
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
