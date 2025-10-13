<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends BaseEntity
{
    protected $table = 'entries';

    protected $fillable = [
        'id',
        'user_id',
        'entry_type_id',
        'title',
        'content',
        'status',
        'published_at',
        'order',
    ];

    protected $casts = [
        'content' => 'json', // Store as JSON but can be simple key-value
        'published_at' => 'datetime',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the type this entry belongs to
     */
    public function entryType(): BelongsTo
    {
        return $this->belongsTo(EntryType::class);
    }

    /**
     * Get all images for this entry
     */
    public function images(): HasMany
    {
        return $this->hasMany(EntryImage::class)->orderBy('field_name')->orderBy('order');
    }

    /**
     * Get validation rules for this entity type
     */
    public static function getValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            'order' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom error messages for validation
     */
    public static function getValidationMessages(): array
    {
        return [
            'title.required' => 'The entry title is required.',
            'title.max' => 'The entry title cannot be longer than 255 characters.',
            'content.required' => 'The entry content is required.',
            'entry_type_id.required' => 'The entry type is required.',
            'entry_type_id.exists' => 'The selected entry type does not exist.',
            'status.in' => 'The status must be either draft or published.',
        ];
    }

    /**
     * Check if the entity has media attachments
     */
    public function hasMedia(): bool
    {
        return $this->images()->exists();
    }

    /**
     * Get media relationships for this entity
     */
    public function getMediaRelationships(): array
    {
        return ['images'];
    }

    /**
     * Get field value by name from dynamic content
     */
    public function getFieldValue(string $fieldName): mixed
    {
        return $this->content[$fieldName] ?? null;
    }

    /**
     * Set field value in dynamic content
     */
    public function setFieldValue(string $fieldName, mixed $value): void
    {
        $content = $this->content ?? [];
        $content[$fieldName] = $value;
        $this->content = $content;
    }

    /**
     * Scope for published entries
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    /**
     * Scope for draft entries
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
