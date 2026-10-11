<?php

namespace App\Http\Requests\Student;

use App\Enums\AnswerOption;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitQuizAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Student;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'selected_option' => ['required', Rule::enum(AnswerOption::class)],
        ];
    }

    /**
     * Return the selected answer option.
     */
    public function selectedOption(): AnswerOption
    {
        return AnswerOption::from($this->string('selected_option')->toString());
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'selected_option.required' => 'Pilih satu jawapan dahulu. Anda boleh!',
            'selected_option.enum' => 'Sila pilih jawapan A, B, C atau D.',
        ];
    }
}
