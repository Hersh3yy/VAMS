<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'album_display_settings' => ['nullable', 'array'],
            'album_display_settings.caption' => ['boolean'],
            'album_display_settings.altText' => ['boolean'],
            'album_display_settings.dateCreated' => ['boolean'],
            'album_display_settings.location' => ['boolean'],
            'album_display_settings.tags' => ['boolean'],
        ];
    }
}
