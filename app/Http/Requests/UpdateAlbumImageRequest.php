<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAlbumImageRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'altText' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'dateCreated' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,heic,heif',
            'published' => 'nullable|boolean',
        ];
    }
}
