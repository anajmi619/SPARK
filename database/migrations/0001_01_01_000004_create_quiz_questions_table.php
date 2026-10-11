<?php

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->unsignedSmallInteger('topic_id');
            $table->unsignedTinyInteger('question_order');
            $table->text('question_text');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->enum('correct_option', array_column(AnswerOption::cases(), 'value'));
            $table->text('curriculum_context')->nullable();
            $table->enum('status', array_column(QuestionStatus::cases(), 'value'))->default(QuestionStatus::Active->value);
            $table->timestamps();

            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('topics')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unique(['topic_id', 'question_order']);
            $table->index(['topic_id', 'status']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE quiz_questions ADD CONSTRAINT chk_quiz_questions_order CHECK (question_order BETWEEN 1 AND 10)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
