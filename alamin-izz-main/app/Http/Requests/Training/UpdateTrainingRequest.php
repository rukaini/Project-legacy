<?php

namespace App\Http\Requests\Training;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuruBesar();
    }

    public function rules(): array
    {
        return [
            'courseName'     => 'required|string|max:255',
            'courseType' => 'nullable|in:LMS,Simple',
            'description'     => 'nullable|string',
            'capacity'        => 'required|integer|min:1',
            'price'                       => 'required|numeric|min:0',
            'mode'                  => 'nullable|in:online,physical,hybrid',
            'course_pic'                  => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, $fail): void {
                    if (is_string($value) && trim($value) !== '') {
                        $fail('Contact Person cannot be stored in the shared canonical database. Please leave it blank.');
                    }
                },
            ],
            'whatsappGroup'              => 'nullable|url|max:255',
            'organiserName'              => 'required|string|max:255',
        ];
    }
}
