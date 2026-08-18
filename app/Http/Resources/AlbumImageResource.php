<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AlbumImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AlbumImage
 */
final class AlbumImageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = is_array($this->properties) ? $this->properties : [];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'path' => $this->path,
            'webp_path' => $this->webp_path ?? null,
            'thumbnail_url' => $properties['thumbnail_url'] ?? $this->path,
            'webp_url' => $properties['webp_url'] ?? null,
            'caption' => $this->caption,
            'order' => $this->order,
            'published' => $this->published,
            'properties' => $properties,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
