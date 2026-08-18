<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Services\Plans\PlanLimitService;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAlbumImageRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'altText' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'dateCreated' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,webp,heic,heif', "max:{$maxUploadKb}"],
            'published' => ['nullable', 'boolean'],
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
