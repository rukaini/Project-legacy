<?php

namespace App\Http\Requests\Certificate;

use App\Models\PenyelarasLadap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuruBesar();
    }

    public function rules(): array
    {
        return [
            'approval'     => ['required', Rule::in([PenyelarasLadap::APPROVAL_APPROVED, PenyelarasLadap::APPROVAL_REJECTED])],
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
