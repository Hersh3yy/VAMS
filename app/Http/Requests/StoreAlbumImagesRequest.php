<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Services\Plans\PlanLimitService;
use Illuminate\Foundation\Http\FormRequest;

final class StoreAlbumImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(PlanLimitService $planLimitService): array
    {
        $maxUploadKb = $this->maxUploadSizeKb($planLimitService);

        return [
            'images' => ['required', 'array'],
            'images.*' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,webp,heic,heif', "max:{$maxUploadKb}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxUploadKb = $this->maxUploadSizeKb($this->container->make(PlanLimitService::class));

        return [
            'images.required' => 'Please select at least one image to upload.',
            'images.array' => 'Images must be provided as an array.',
            'images.*.required' => 'One or more image files are missing.',
            'images.*.file' => 'The uploaded file failed to upload. This may be due to file size limits, network issues, or unsupported file type.',
            'images.*.max' => 'Each image must be '.round($maxUploadKb / 1024).'MB or smaller on your current plan.',
            'images.*.mimes' => 'The file must be one of: jpeg, png, jpg, gif, webp, heic, heif. Detected type: :attribute',
            'images.*.image' => 'All files must be valid images (jpeg, png, jpg, gif, etc.).',
        ];
    }

    private function maxUploadSizeKb(PlanLimitService $planLimitService): int
    {
        $user = $this->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $planLimitService->maxUploadSizeKb($user);
    }
}
