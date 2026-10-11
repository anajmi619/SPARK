<?php

namespace Database\Factories;

use App\Enums\TopicStatus;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_order' => fake()->unique()->numberBetween(1, 12),
            'topic_name' => fake()->unique()->words(2, true),
            'content_text' => fake()->paragraphs(3, true),
            'image_path' => null,
            'pdf_path' => null,
            'youtube_url' => null,
            'status' => TopicStatus::Draft,
            'created_by' => null,
        ];
    }
}
