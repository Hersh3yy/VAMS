<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Mosaic extends Model
{
    use HasUuids;

    protected $table = 'landing_mosaics';

    protected $fillable = [
        'title',
        'description',
        'theme_settings',
    ];

    protected $casts = [
        'theme_settings' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(MosaicItem::class, 'landing_mosaic_id')->orderBy('order');
    }
}
