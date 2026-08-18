<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesMosaicOwnership;
use App\Models\User;
use App\Services\Plans\PlanLimitService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class StoreMosaicMediaRequest extends FormRequest
{
    use AuthorizesMosaicOwnership;

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(PlanLimitService $planLimitService): array
    {
        $maxUploadKb = $this->maxUploadSizeKb($planLimitService);

        return [
            'media' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,mp4,mov,avi,webp', "max:{$maxUploadKb}"],
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('media');
        assert($file instanceof UploadedFile);

        return $file;
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
