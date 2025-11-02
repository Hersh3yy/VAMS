<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MosaicItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'id',
        'mosaic_id',
        'column_index',
        'type',
        'content',
        'album_id',
        'properties',
        'order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'content' => 'array',
            'is_active' => 'boolean',
            'column_index' => 'integer',
            'order' => 'integer',
        ];
    }

    public function mosaic(): BelongsTo
    {
        return $this->belongsTo(Mosaic::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Get images for this mosaic item
     *
     * @return Collection<int, AlbumImage>
     */
    public function getImages(): Collection
    {
        if ($this->type === 'album' && $this->album) {
            return $this->album->images;
        }

        return collect($this->content ?? []);
    }

    /**
     * Add an image path to this item's content
     */
    public function addImage(string $imagePath): static
    {
        $content = $this->content ?? [];
        $content[] = $imagePath;
        $this->content = $content;

        return $this;
    }

    /**
     * Remove an image path from this item's content
     */
    public function removeImage(string $imagePath): static
    {
        $content = $this->content ?? [];
        $content = array_filter($content, fn ($path) => $path !== $imagePath);
        $this->content = array_values($content);

        return $this;
    }
}
