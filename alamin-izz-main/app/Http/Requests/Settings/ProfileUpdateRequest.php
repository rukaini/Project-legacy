<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        if ($user->isTeacher() || $user->isGuruBesar()) {
            return [
                'phone_number' => ['nullable', 'string', 'max:20'],
                'address'      => ['nullable', 'string', 'max:255'],
            ];
        }

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ];

        if ($user->isOutsider()) {
            $rules['organization'] = ['nullable', 'string', 'max:255'];
            $rules['phone_number_outsider'] = ['nullable', 'regex:/^01\d-\d{7,8}$/'];
            $rules['ic_number']             = ['nullable', 'regex:/^\d{6}-\d{2}-\d{4,5}$/'];
            $rules['gender']                = ['nullable', 'in:Male,Female'];
        }

        return $rules;
    }
}
