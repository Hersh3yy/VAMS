<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Mosaic extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'mosaics';

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'description',
        'columns',
    ];

    protected $casts = [
        'columns' => 'integer',
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
}
