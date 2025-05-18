<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MosaicItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'mosaic_id',
        'type',
        'split_direction',
        'desktop_position',
        'order',
        'parent_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function mosaic()
    {
        return $this->belongsTo(Mosaic::class);
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
