<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Mosaic extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'mosaics';

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'description',
        'columns',
        'display_settings',
    ];

    protected $casts = [
        'columns' => 'integer',
        'display_settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(MosaicItem::class)
            ->orderBy('column_index')
            ->orderBy('order');
    }

    // Get items for a specific column
    public function itemsInColumn($columnIndex)
    {
        return $this->hasMany(MosaicItem::class)
            ->where('column_index', $columnIndex)
            ->orderBy('order');
    }

    public function media()
    {
        return $this->belongsToMany(Media::class)
            ->withPivot('order')
            ->orderBy('pivot_order');
    }
}
