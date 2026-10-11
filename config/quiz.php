<?php

return [
    'ai_feedback_enabled' => env('QUIZ_AI_FEEDBACK_ENABLED', false),
    'ai_feedback_per_student_hourly_limit' => (int) env('QUIZ_AI_FEEDBACK_PER_STUDENT_HOURLY_LIMIT', 10),
    'ai_feedback_daily_limit' => (int) env('QUIZ_AI_FEEDBACK_DAILY_LIMIT', 100),
    'ai_feedback_cache_days' => (int) env('QUIZ_AI_FEEDBACK_CACHE_DAYS', 30),
    'image_max_size_kilobytes' => 5 * 1024,
];
