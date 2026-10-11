<?php

namespace App\Rules;

use App\Support\YouTubeUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YouTubeLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || YouTubeUrl::videoId($value) === null) {
            $fail('Sila masukkan pautan YouTube yang sah (watch, youtu.be, embed atau shorts).');
        }
    }
}
