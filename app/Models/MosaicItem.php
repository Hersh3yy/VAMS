<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MosaicItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'album_id',
        'image_path',
        'title',
        'description',
        'link_url',
        'desktop_position',
        'mobile_position',
        'order'
    ];

    protected $casts = [
        'desktop_position' => 'json',
        'mobile_position' => 'json'
    ];

    public function mosaic()
    {
        return $this->belongsTo(Mosaic::class, 'landing_mosaic_id');
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}
