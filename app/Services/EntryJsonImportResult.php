<?php

declare(strict_types=1);

namespace App\Services;

use App\Adapters\AdaptedEntry;
use App\Models\EntryType;

/**
 * Outcome of preparing a JSON import — no HTTP concerns.
 *
 * @phpstan-type FieldErrors array<string, list<string>|string>
 */
final readonly class EntryJsonImportResult
{
    /**
     * @param  list<AdaptedEntry>|null  $entries
     * @param  array<mixed>|null  $payload
     * @param  FieldErrors|null  $errors
     */
    private function __construct(
        public bool $successful,
        public ?EntryType $entryType = null,
        public ?array $entries = null,
        public ?array $payload = null,
        public ?array $errors = null,
        public ?string $flashError = null,
    ) {}

    /**
     * @param  list<AdaptedEntry>  $entries
     * @param  array<mixed>  $payload
     */
    public static function success(EntryType $entryType, array $entries, array $payload): self
    {
        return new self(
            successful: true,
            entryType: $entryType,
            entries: $entries,
            payload: $payload,
        );
    }

    /**
     * @param  FieldErrors  $errors
     */
    public static function invalid(array $errors): self
    {
        return new self(successful: false, errors: $errors);
    }

    public static function flashFailure(string $message): self
    {
        return new self(successful: false, flashError: $message);
    }

    /**
     * @return list<array{title: string, content: array<string, mixed>, status: string}>
     */
    public function entriesAsArrays(): array
    {
        return array_map(
            static fn (AdaptedEntry $entry): array => $entry->toArray(),
            $this->entries ?? [],
        );
    }
}
