<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Mosaic extends Model
{
    use HasUuids;

    protected $table = 'mosaics';

    protected $fillable = [
        'title',
        'description',
        'layout_settings',
        'columns',
        'user_id',
    ];

    protected $casts = [
        'layout_settings' => 'json',
        'columns' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(MosaicItem::class, 'mosaic_id')->orderBy('order');
    }
    
    // Get root level items (those without a parent)
    public function rootItems()
    {
        return $this->hasMany(MosaicItem::class, 'mosaic_id')
            ->whereNull('parent_id')
            ->orderBy('order');
    }
}
