<?php

namespace App\Http\Requests\PenyelarasLadap;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePenyelarasAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuruBesar();
    }

    public function rules(): array
    {
        return [
            'credit_hours' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'remarks'      => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'credit_hours.required' => 'Please enter the credit hours.',
        ];
    }
}
