<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterUserAccountsRequest extends FormRequest
{
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
            'search' => ['nullable', 'string', 'max:100'],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'academic_year_id')],
            'kelas_id' => ['nullable', 'integer', Rule::exists('kelas', 'id')],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ];
    }
}
