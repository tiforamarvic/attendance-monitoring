<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'schedule_start' => ['nullable', 'array'],
            'schedule_start.*' => ['nullable', 'date_format:H:i'],
            'schedule_end' => ['nullable', 'array'],
            'schedule_end.*' => ['nullable', 'date_format:H:i'],
        ];
    }
}
