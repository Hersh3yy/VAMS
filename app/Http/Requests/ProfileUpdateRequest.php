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
            'album_display_settings.caption' => ['nullable', 'boolean'],
            'album_display_settings.altText' => ['nullable', 'boolean'],
            'album_display_settings.dateCreated' => ['nullable', 'boolean'],
            'album_display_settings.location' => ['nullable', 'boolean'],
            'album_display_settings.tags' => ['nullable', 'boolean'],
            'album_display_settings.title' => ['nullable', 'boolean'],
            'album_display_settings.author' => ['nullable', 'boolean'],
            'album_display_settings.main_color' => ['nullable', 'string'],
            'album_display_settings.secondary_color' => ['nullable', 'string'],
        ];
    }
}
