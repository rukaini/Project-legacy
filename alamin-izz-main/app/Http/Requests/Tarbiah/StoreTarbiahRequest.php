<?php

namespace App\Http\Requests\Tarbiah;

use Illuminate\Foundation\Http\FormRequest;

class StoreTarbiahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuruBesar();
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'session_date' => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:session_date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'credit_hours' => 'required|numeric|min:0.5|max:99.99',
            'location'     => 'nullable|string|max:255',
        ];
    }
}
