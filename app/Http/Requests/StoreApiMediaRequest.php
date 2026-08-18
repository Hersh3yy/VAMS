<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Services\Plans\PlanLimitService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class StoreApiMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(PlanLimitService $planLimitService): array
    {
        $user = $this->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $maxUploadKb = $planLimitService->maxUploadSizeKb($user);

        return [
            'file' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,svg,mp4,webm,avi,webp', "max:{$maxUploadKb}"],
            'type' => ['sometimes', 'string', 'in:image,video'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A media file is required.',
            'file.file' => 'The upload must be a valid file.',
            'file.mimes' => 'The file must be an image or video of an allowed type.',
            'file.max' => 'The file exceeds the maximum upload size for your plan.',
            'type.in' => 'The media type must be image or video.',
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('file');
        assert($file instanceof UploadedFile);

        return $file;
    }

    public function mediaType(): string
    {
        return $this->string('type', 'image')->toString();
    }
}
