<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AlbumImage;
use App\Models\EntryImage;
use App\Services\ImageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class DeleteApiMediaRequest extends FormRequest
{
    /**
     * A path may be deleted only when it is still in the caller's own upload
     * folder (uploaded but not yet attached), or when it belongs to an album or
     * entry image the caller may update. A guessed or borrowed path is denied.
     */
    public function authorize(): bool
    {
        $path = $this->storagePath();
        $userId = (string) $this->user()?->id;

        // No path: let validation answer with a 422 instead of a 403.
        if ($path === '') {
            return true;
        }

        if ($userId !== '' && (str_starts_with($path, "uploads/images/{$userId}/")
            || str_starts_with($path, "uploads/videos/{$userId}/"))) {
            return true;
        }

        // Album and entry images store the full public URL, so check both forms.
        $candidates = [$path, app(ImageService::class)->getPublicUrl($path)];

        $albumImage = AlbumImage::whereIn('path', $candidates)->first();
        if ($albumImage) {
            return Gate::allows('update', $albumImage->album);
        }

        $entryImage = EntryImage::whereIn('path', $candidates)->first();
        if ($entryImage) {
            return Gate::allows('update', $entryImage->entry);
        }

        return false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'path' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'path.required' => 'A file path is required.',
            'path.string' => 'The file path must be a string.',
        ];
    }

    /** The storage path, with the Spaces endpoint and bucket prefix stripped from a full URL. */
    public function storagePath(): string
    {
        $path = (string) $this->input('path', '');
        $endpoint = (string) config('filesystems.disks.spaces.endpoint');

        if ($endpoint !== '' && str_contains($path, $endpoint)) {
            $bucket = config('filesystems.disks.spaces.bucket');
            $path = str_replace("{$endpoint}/{$bucket}/", '', $path);
        }

        return ltrim($path, '/');
    }
}
