<?php

namespace App\Http\Requests\Teacher;

use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ResetStudentPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => $this->passwordRules(),
        ];
    }
}
