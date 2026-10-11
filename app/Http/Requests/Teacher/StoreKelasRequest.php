<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nama'))) {
            $this->merge(['nama' => Str::squish($this->input('nama'))]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:50', 'not_regex:/^(?:tahun\\s+)?[0-9]+(?:\\s|$)/iu', Rule::unique('kelas', 'nama')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['nama.not_regex' => 'Masukkan nama kelas sahaja tanpa nombor tahun, contohnya Ibnu Sina.'];
    }
}
