<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['boolean'],
            'is_approved' => ['boolean'],
            'entry_type_permissions' => ['nullable', 'array'],
            'entry_type_permissions.*' => ['string', 'exists:entry_types,slug'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email address already exists.',
            'password.min' => 'The password must be at least 8 characters.',
            'entry_type_permissions.*.exists' => 'One of the selected entry types does not exist.',
        ];
    }
}
