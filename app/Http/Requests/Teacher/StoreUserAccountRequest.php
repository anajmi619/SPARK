<?php

namespace App\Http\Requests\Teacher;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUserAccountRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Normalize a username before its uniqueness check and storage.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Str::lower($this->string('username')->trim()->toString()),
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
            ...$this->profileRules(),
            'role' => ['required', Rule::in([UserRole::Student->value, UserRole::Teacher->value])],
            'academic_year_id' => [
                Rule::requiredIf($this->input('role') === UserRole::Student->value),
                Rule::prohibitedIf($this->input('role') !== UserRole::Student->value),
                'nullable',
                'integer',
                Rule::exists('academic_years', 'academic_year_id')->whereIn('status', [
                    AcademicYearStatus::Active->value,
                    AcademicYearStatus::Upcoming->value,
                ]),
            ],
            'kelas_id' => [
                Rule::requiredIf($this->input('role') === UserRole::Student->value),
                Rule::prohibitedIf($this->input('role') !== UserRole::Student->value),
                'nullable',
                'integer',
                Rule::exists('kelas', 'id'),
            ],
            'kelas_ids' => [
                Rule::requiredIf($this->input('role') === UserRole::Teacher->value),
                Rule::prohibitedIf($this->input('role') !== UserRole::Teacher->value),
                'nullable',
                'array',
                'min:1',
            ],
            'kelas_ids.*' => ['integer', 'distinct', Rule::exists('kelas', 'id')],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * Return normalized account creation data.
     *
     * @return array{full_name: string, username: string, password: string, role: string, academic_year_id: int|null, kelas_id: int|null, kelas_ids: list<int>}
     */
    public function accountData(): array
    {
        $validated = $this->validated();

        return [
            'full_name' => $this->string('full_name')->toString(),
            'username' => $this->string('username')->toString(),
            'password' => $this->string('password')->toString(),
            'role' => $this->string('role')->toString(),
            'academic_year_id' => isset($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : null,
            'kelas_id' => isset($validated['kelas_id']) ? (int) $validated['kelas_id'] : null,
            'kelas_ids' => array_values(array_map('intval', $validated['kelas_ids'] ?? [])),
        ];
    }
}
