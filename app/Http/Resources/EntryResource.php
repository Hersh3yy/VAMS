<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entry
 */
final class EntryResource extends JsonResource
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
            'content' => $this->content,
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'order' => $this->order,
            'entry_type' => [
                'id' => $this->entryType->id,
                'name' => $this->entryType->name,
                'slug' => $this->entryType->slug,
            ],
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
