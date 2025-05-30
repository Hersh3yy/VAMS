<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Album extends Model
{
    use HasFactory, HasUuids, LogsActivity;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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