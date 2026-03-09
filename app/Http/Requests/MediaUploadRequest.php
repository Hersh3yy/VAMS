<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MediaUploadRequest extends FormRequest
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
        if ($this->hasFile('file') || $this->has('type')) {
            return [
                'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,mp4,webm,avi',
                'type' => 'sometimes|string|in:image,video',
            ];
        }

        return [
            'entity_type' => 'required|string|in:album,blog,news,mosaic,entry',
            'entity_id' => 'required|string',
            'media' => 'required|array',
            'media.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,mp4,webm,avi',
        ];
    }
}
