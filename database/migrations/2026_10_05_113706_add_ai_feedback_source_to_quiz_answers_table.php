<?php

use App\Enums\AiFeedbackSource;
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
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->enum('ai_feedback_source', array_column(AiFeedbackSource::cases(), 'value'))
                ->nullable()
                ->after('ai_feedback_status')
                ->index();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE quiz_answers ADD CONSTRAINT chk_quiz_answers_feedback_source CHECK (
                    ai_feedback_source IS NULL OR is_correct = 0
                )
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE quiz_answers DROP CHECK chk_quiz_answers_feedback_source');
        }

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropIndex(['ai_feedback_source']);
            $table->dropColumn('ai_feedback_source');
        });
    }
};
