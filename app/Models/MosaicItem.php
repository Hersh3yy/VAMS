<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $casts = [
        'properties' => 'array',
        'content' => 'array',
        'is_active' => 'boolean',
        'column_index' => 'integer',
        'order' => 'integer',
    ];

    public function mosaic()
    {
        return $this->belongsTo(Mosaic::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function getImages()
    {
        if ($this->type === 'album' && $this->album) {
            return $this->album->images;
        }

        return collect($this->content ?? []);
    }

    public function addImage($imagePath)
    {
        $content = $this->content ?? [];
        $content[] = $imagePath;
        $this->content = $content;

        return $this;
    }

    public function removeImage($imagePath)
    {
        $content = $this->content ?? [];
        $content = array_filter($content, fn ($path) => $path !== $imagePath);
        $this->content = array_values($content);

        return $this;
    }
}
