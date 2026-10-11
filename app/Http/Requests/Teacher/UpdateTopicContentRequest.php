<?php

namespace App\Http\Requests\Teacher;

use App\Concerns\TopicContentValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTopicContentRequest extends FormRequest
{
    use TopicContentValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true;
    }

    /**
     * Normalize optional learning-content fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareTopicContentForValidation();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->topicContentRules();
    }
}
