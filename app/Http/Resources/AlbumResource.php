<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Album
 */
final class AlbumResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_path' => $this->cover_image_path,
            'images_count' => $this->images_count ?? $this->images->count(),
            'user_id' => $this->user_id,
            'published' => $this->published,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
