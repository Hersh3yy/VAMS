<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EntryCollection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'slug',
        'description',
        'field_config',
        'order',
        'is_active',
    ];

    protected $casts = [
        'field_config' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this collection
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all entries in this collection
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class)->orderBy('order');
    }

    /**
     * Get published entries in this collection
     */
    public function publishedEntries(): HasMany
    {
        return $this->hasMany(Entry::class)
            ->where('status', 'published')
            ->orderBy('order');
    }

    /**
     * Auto-generate slug from name
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($collection) {
            if (empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });

        static::updating(function ($collection) {
            if ($collection->isDirty('name') && empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });
    }

    /**
     * Get default field configuration for simple text entries
     */
    public static function getDefaultFieldConfig(): array
    {
        return [
            [
                'name' => 'content',
                'type' => 'textarea',
                'label' => 'Content',
                'required' => true,
                'placeholder' => 'Enter your content...'
            ]
        ];
    }

    /**
     * Get field configuration for "I AMS" collection
     */
    public static function getIAmsFieldConfig(): array
    {
        return [
            [
                'name' => 'statement',
                'type' => 'textarea',
                'label' => 'I AM...',
                'required' => true,
                'placeholder' => 'I AM...'
            ]
        ];
    }

    /**
     * Scope for active collections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered collections
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
