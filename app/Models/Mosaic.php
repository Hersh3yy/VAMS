<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;

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

    protected array $casts = [
        'columns' => 'integer',
        'display_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MosaicItem::class)
            ->orderBy('column_index')
            ->orderBy('order');
    }

    // Get items for a specific column
    public function itemsInColumn(int $columnIndex): HasMany
    {
        return $this->hasMany(MosaicItem::class)
            ->where('column_index', $columnIndex)
            ->orderBy('order');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class)
            ->withPivot('order')
            ->orderBy('pivot_order');
    }

    public function getItemsForColumn(int $columnIndex): Collection
    {
        return $this->items->where('column_index', $columnIndex);
    }
}
