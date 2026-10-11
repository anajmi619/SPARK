<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->dropColumn('video_path');
            $table->string('youtube_url', 500)->nullable();
        });
        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->string('image_path')->nullable();
            foreach (['a', 'b', 'c', 'd'] as $option) {
                $table->string("option_{$option}_image_path")->nullable();
            }
            $table->text('explanation')->nullable();
        });
        Schema::table('quiz_answers', function (Blueprint $table): void {
            $table->text('explanation_snapshot')->nullable();
            $table->json('presentation_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            $table->dropColumn(['explanation_snapshot', 'presentation_snapshot']);
        });
        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'option_a_image_path', 'option_b_image_path', 'option_c_image_path', 'option_d_image_path', 'explanation']);
        });
        Schema::table('topics', function (Blueprint $table): void {
            $table->dropColumn('youtube_url');
            $table->string('video_path', 500)->nullable();
        });
    }
};
