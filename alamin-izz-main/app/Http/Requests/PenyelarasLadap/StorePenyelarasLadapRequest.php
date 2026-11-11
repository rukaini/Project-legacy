<?php

namespace App\Http\Requests\PenyelarasLadap;

use Illuminate\Foundation\Http\FormRequest;

class StorePenyelarasLadapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuruBesar();
    }

    public function rules(): array
    {
        return [
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'session_date'        => 'required|date',
            'start_time'          => 'required|date_format:H:i',
            'end_time'            => 'required|date_format:H:i|after:start_time',
            'location'            => 'nullable|string|max:255',
            'assigned_teacher_id' => 'required|string|max:20',
        ];
    }
}
