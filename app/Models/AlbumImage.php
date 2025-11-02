<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlbumImage extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'album_id',
        'path',
        'title',
        'alt_text',
        'caption',
        'author',
        'date_created',
        'location',
        'tags',
        'order',
        'properties',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'json',
            'order' => 'integer',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Relationship to image variants
     *
     * Note: AlbumImageVariant model will be created in Phase 1
     * This method is prepared now for backwards compatibility planning
     *
     * @return HasMany
     */
    public function variants(): HasMany
    {
        // TODO: Uncomment when AlbumImageVariant model is created in Phase 1
        // return $this->hasMany(AlbumImageVariant::class);

        // Temporary placeholder - will be replaced in Phase 1
        return $this->hasMany(static::class)->whereRaw('1 = 0');
    }

    /**
     * Check if this is a video entry
     */
    public function getIsVideoAttribute(): bool
    {
        if (isset($this->properties['type']) && $this->properties['type'] === 'video') {
            return true;
        }

        if (isset($this->properties['is_video'])) {
            return (bool) $this->properties['is_video'];
        }

        // Legacy check based on path
        return
            str_contains($this->path, 'youtube.com') ||
            str_contains($this->path, 'youtu.be') ||
            str_contains($this->path, 'vimeo.com');
    }

    /**
     * Check if this image has been migrated to variant system
     *
     * @return bool
     */
    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }
}
