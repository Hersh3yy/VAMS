<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

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
        'properties',
        'order',
        'is_active'
    ];

    protected $casts = [
        'properties' => 'array',
        'is_active' => 'boolean',
        'column_index' => 'integer',
        'order' => 'integer'
    ];

    public function mosaic()
    {
        return $this->belongsTo(Mosaic::class);
    }
}
