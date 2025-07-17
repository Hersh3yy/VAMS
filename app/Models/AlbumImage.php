<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlbumImage extends Model
{
    use HasFactory, HasUuids;

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

    protected $casts = [
        'properties' => 'json',
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
    
    // Helper method to check if this is a video
    public function getIsVideoAttribute()
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