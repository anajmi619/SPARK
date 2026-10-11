<?php

namespace App\Http\Requests\Teacher;

use App\Concerns\TopicContentValidationRules;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopicModuleRequest extends FormRequest
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
     * Normalize a topic name and optional learning text before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareTopicContentForValidation();

        $this->merge([
            'topic_name' => trim((string) $this->input('topic_name')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'topic_name' => ['required', 'string', 'max:100', Rule::unique(Topic::class, 'topic_name')],
            ...$this->topicContentRules(),
        ];
    }

    /**
     * Return the validated module name.
     */
    public function topicName(): string
    {
        return $this->string('topic_name')->toString();
    }
}
