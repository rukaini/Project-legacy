<?php

namespace App\Http\Requests\Certificate;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'session_date' => 'required|date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'location'     => 'nullable|string|max:255',
            'certificate'  => 'required|file|mimes:pdf|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'certificate.mimes' => 'The certificate must be a PDF file.',
            'certificate.max'   => 'The certificate may not be larger than 10 MB.',
        ];
    }
}
