<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\EntityContract;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Base entity model that provides common functionality for all entities
 * Extend this class instead of Model for new entities
 */
abstract class BaseEntity extends Model implements EntityContract
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the entity's title/name for display purposes
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the entity's description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get the user that owns this entity
     */
    public function getOwner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the entity has media attachments
     */
    abstract public function hasMedia(): bool;

    /**
     * Get media relationships for this entity
     */
    abstract public function getMediaRelationships(): array;

    /**
     * Get validation rules for this entity type
     */
    abstract public static function getValidationRules(): array;

    /**
     * Get custom error messages for validation
     */
    public static function getValidationMessages(): array
    {
        return [];
    }

    /**
     * Get the route parameter name for this entity
     * Override in child classes if needed (e.g., 'album' vs 'mosaic')
     */
    public static function getRouteParameterName(): string
    {
        $className = class_basename(static::class);

        return strtolower($className);
    }

    /**
     * Get the plural route parameter name for this entity
     */
    public static function getRouteParameterNamePlural(): string
    {
        return static::getRouteParameterName().'s';
    }

    /**
     * Get the route name prefix for this entity
     */
    public static function getRouteNamePrefix(): string
    {
        return static::getRouteParameterNamePlural().'.';
    }
}
