<?php

use App\Enums\TopicStatus;
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
        Schema::create('topics', function (Blueprint $table) {
            $table->smallIncrements('topic_id');
            $table->unsignedTinyInteger('topic_order')->unique();
            $table->string('topic_name', 100)->unique();
            $table->longText('content_text')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->enum('status', array_column(TopicStatus::cases(), 'value'))->default(TopicStatus::Draft->value);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->index(['status', 'topic_order']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE topics ADD CONSTRAINT chk_topics_order CHECK (topic_order BETWEEN 1 AND 12)');
            DB::statement("ALTER TABLE topics ADD CONSTRAINT chk_topics_published_content CHECK (status <> 'published' OR content_text IS NOT NULL)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
