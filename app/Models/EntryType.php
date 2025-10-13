<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EntryType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'field_config',
        'is_active',
    ];

    protected $casts = [
        'field_config' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all entries of this type
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'entry_type_id')->orderBy('order');
    }

    /**
     * Auto-generate slug from name
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entryType) {
            if (empty($entryType->slug)) {
                $entryType->slug = Str::slug($entryType->name);
            }
        });

        static::updating(function ($entryType) {
            if ($entryType->isDirty('name') && empty($entryType->slug)) {
                $entryType->slug = Str::slug($entryType->name);
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
                'placeholder' => 'Enter your content...',
            ],
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
                'placeholder' => 'I AM...',
            ],
        ];
    }

    /**
     * Scope for active entry types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
