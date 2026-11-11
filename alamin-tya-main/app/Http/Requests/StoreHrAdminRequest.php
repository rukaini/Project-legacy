<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreHrAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(session('role'), ['hr', 'HR'], true);
    }

    public function rules(): array
    {
        /** @var \App\Models\HrAdministrator|null $hrAdmin */
        $hrAdmin  = $this->route('hrAdmin');
        $ignoreId = $hrAdmin?->getKey();

        return [
            'username' => [
                'required', 'string', 'max:120',
                Rule::unique('hr_administrator', 'username')->ignore($ignoreId, 'hrid'),
            ],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('hr_administrator', 'email')->ignore($ignoreId, 'hrid'),
            ],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role'         => ['nullable', 'string', 'max:50'],

            'password' => $ignoreId
                ? ['nullable', 'confirmed', Password::min(8)->letters()->numbers()]
                : ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return [
            'phone_number' => 'phone number',
        ];
    }
}