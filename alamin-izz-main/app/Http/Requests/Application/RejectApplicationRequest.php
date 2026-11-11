<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class RejectApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuruBesar();
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:1000',
        ];
    }
}
