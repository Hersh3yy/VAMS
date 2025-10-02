<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mosaic extends BaseEntity
{
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get validation rules for this entity type
     */
    public static function getValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'columns' => ['nullable', 'integer', 'min:2', 'max:5'],
            'display_settings' => ['nullable', 'array'],
        ];
    }

    /**
     * Check if the entity has media attachments
     */
    public function hasMedia(): bool
    {
        return true;
    }

    /**
     * Get media relationships for this entity
     */
    public function getMediaRelationships(): array
    {
        return ['items', 'media'];
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
