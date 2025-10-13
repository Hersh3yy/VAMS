<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Base contract that all entities must implement
 * This ensures consistency across all entity types
 */
interface EntityContract
{
    /**
     * Get the entity's title/name for display purposes
     */
    public function getTitle(): string;

    /**
     * Get the entity's description
     */
    public function getDescription(): ?string;

    /**
     * Get the user that owns this entity
     */
    public function getOwner(): BelongsTo;

    /**
     * Check if the entity has media attachments
     */
    public function hasMedia(): bool;

    /**
     * Get media relationships for this entity
     */
    public function getMediaRelationships(): array;

    /**
     * Get validation rules for this entity type
     */
    public static function getValidationRules(): array;

    /**
     * Get custom error messages for validation
     */
    public static function getValidationMessages(): array;
}
