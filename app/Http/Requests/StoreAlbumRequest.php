<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by policies
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,png,jpg,gif'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The album title is required.',
            'title.max' => 'The album title cannot be longer than 255 characters.',
            'cover_image.image' => 'The cover image must be a valid image file.',
            'cover_image.max' => 'The cover image cannot be larger than 5MB.',
            'cover_image.mimes' => 'The cover image must be a jpeg, png, jpg or gif file.',
        ];
    }
} 