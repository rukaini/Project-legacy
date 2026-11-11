<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isParticipant();
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|string|max:20|exists:course_session,sessionID',
            'remarks'    => 'nullable|string|max:500',
        ];
    }
}
