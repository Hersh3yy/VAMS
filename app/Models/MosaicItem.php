<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MosaicItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'mosaic_id',
        'parent_id',
        'split_direction',
        'type',
        'reference_id',
        'content',
        'properties',
        'link_url',
        'link_target',
        'desktop_position',
        'tablet_position',
        'mobile_position',
        'order',
        'is_active'
    ];

    protected $casts = [
        'properties' => 'json',
        'desktop_position' => 'json',
        'tablet_position' => 'json',
        'mobile_position' => 'json',
        'is_active' => 'boolean'
    ];

    public function mosaic()
    {
        return $this->belongsTo(Mosaic::class, 'mosaic_id');
    }

    public function album()
    {
        return $this->belongsTo(Album::class, 'reference_id')->when(
            $this->type === 'album'
        );
    }

    public function parent()
    {
        return $this->belongsTo(MosaicItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MosaicItem::class, 'parent_id');
    }
}
