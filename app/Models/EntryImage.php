<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryImage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'entry_id',
        'field_name',
        'path',
        'title',
        'alt_text',
        'caption',
        'order',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the entry this image belongs to
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * Check if this is a video (reusing AlbumImage logic)
     */
    public function getIsVideoAttribute(): bool
    {
        if (isset($this->properties['is_video'])) {
            return (bool) $this->properties['is_video'];
        }
        
        // Legacy check based on path
        return 
            strpos($this->path, 'youtube.com') !== false || 
            strpos($this->path, 'youtu.be') !== false || 
            strpos($this->path, 'vimeo.com') !== false;
    }
}
