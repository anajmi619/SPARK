<?php

use App\Enums\AiFeedbackStatus;
use App\Enums\AnswerOption;
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
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id('answer_id');
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->enum('selected_option', array_column(AnswerOption::cases(), 'value'));
            $table->boolean('is_correct');
            $table->text('question_snapshot');
            $table->string('selected_answer_snapshot');
            $table->string('correct_answer_snapshot');
            $table->text('ai_feedback_text')->nullable();
            $table->enum('ai_feedback_status', array_column(AiFeedbackStatus::cases(), 'value'))->default(AiFeedbackStatus::NotRequired->value);
            $table->dateTime('answered_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('attempt_id')
                ->references('attempt_id')
                ->on('quiz_attempts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('question_id')
                ->references('question_id')
                ->on('quiz_questions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unique(['attempt_id', 'question_id']);
            $table->index('question_id');
            $table->index('ai_feedback_status');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE quiz_answers ADD CONSTRAINT chk_quiz_answers_feedback CHECK (
                    (is_correct = 1
                        AND ai_feedback_status = 'not_required'
                        AND ai_feedback_text IS NULL)
                    OR
                    (is_correct = 0
                        AND ai_feedback_status = 'generated'
                        AND ai_feedback_text IS NOT NULL)
                    OR
                    (is_correct = 0
                        AND ai_feedback_status = 'failed')
                )
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
