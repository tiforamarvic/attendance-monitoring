<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'class_room_id' => ['required', 'exists:class_rooms,id'],
            'session_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attendance' => ['required', 'array'],
            'attendance.*.status' => ['required', 'in:present,late,absent,excused'],
            'attendance.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
