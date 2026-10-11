<?php

namespace App\Http\Requests\Teacher;

use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'rollover_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'confirm' => ['accepted'],
        ];
    }

    public function rolloverDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $this->string('rollover_at')->toString(), 'Asia/Kuala_Lumpur')->utc();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'rollover_at.required' => 'Pilih tarikh dan masa peralihan.',
            'rollover_at.date_format' => 'Tarikh dan masa peralihan tidak sah.',
            'confirm.accepted' => 'Sahkan jadual peralihan sebelum menyimpan.',
        ];
    }
}
