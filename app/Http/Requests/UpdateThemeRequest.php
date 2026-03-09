<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'album_display_settings' => ['required', 'array'],
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
