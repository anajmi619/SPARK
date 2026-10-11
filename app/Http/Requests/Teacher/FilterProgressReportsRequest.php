<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterProgressReportsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true
            && $this->user()->status === UserStatus::Active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'academic_year_id')],
            'kelas_id' => ['nullable', 'integer', Rule::exists('kelas', 'id')],
            'search' => ['nullable', 'string', 'max:100'],
            'support_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'academic_year_id' => 'tahun akademik', 'kelas_id' => 'kelas',
            'search' => 'carian', 'support_only' => 'penapis sokongan', 'page' => 'halaman',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'exists' => 'Pilihan :attribute tidak sah.',
            'integer' => ':attribute mestilah nombor bulat.',
            'string' => ':attribute mestilah teks.',
            'max.string' => ':attribute tidak boleh melebihi :max aksara.',
            'boolean' => 'Pilihan :attribute tidak sah.',
            'min' => ':attribute mestilah sekurang-kurangnya :min.',
        ];
    }
}
