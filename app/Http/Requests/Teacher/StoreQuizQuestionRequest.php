<?php

namespace App\Http\Requests\Teacher;

use App\Concerns\QuizQuestionValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuizQuestionRequest extends FormRequest
{
    use QuizQuestionValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true;
    }

    /**
     * Normalize question text and answer options before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareQuizQuestionForValidation();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->quizQuestionRules();
    }
}
