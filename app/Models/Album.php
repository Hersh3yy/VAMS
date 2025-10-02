<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseEntity;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends BaseEntity
{
    protected $table = 'albums';

    protected $fillable = [
        'title',
        'description',
        'order',
        'cover_image_path',
        'user_id',
    ];

    protected $casts = [
        'order' => 'integer',
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
            'cover_image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,png,jpg,gif'],
        ];
    }

    /**
     * Get custom error messages for validation
     */
    public static function getValidationMessages(): array
    {
        return [
            'title.required' => 'The album title is required.',
            'title.max' => 'The album title cannot be longer than 255 characters.',
            'cover_image.image' => 'The cover image must be a valid image file.',
            'cover_image.max' => 'The cover image cannot be larger than 5MB.',
            'cover_image.mimes' => 'The cover image must be a jpeg, png, jpg or gif file.',
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
        return ['images', 'media'];
    }

    public function images(): HasMany
    {
        return $this->hasMany(AlbumImage::class)->orderBy('order');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class)
            ->withPivot('order')
            ->orderBy('pivot_order');
    }

    public function getImagesCountAttribute(): int
    {
        return $this->media()->count();
    }
} 