<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAlbumImageRequest extends FormRequest
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
            'images' => 'required|array',
            'images.*' => 'required|file|mimes:jpeg,png,jpg,gif,webp,heic,heif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Please select at least one image to upload.',
            'images.array' => 'Images must be provided as an array.',
            'images.*.required' => 'One or more image files are missing.',
            'images.*.file' => 'The uploaded file failed to upload. This may be due to file size limits, network issues, or unsupported file type.',
            'images.*.mimes' => 'The file must be one of: jpeg, png, jpg, gif, webp, heic, heif. Detected type: :attribute',
            'images.*.image' => 'All files must be valid images (jpeg, png, jpg, gif, etc.).',
        ];
    }
}
