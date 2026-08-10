<?php

declare(strict_types=1);

namespace App\Actions\Entries;

use App\Models\Entry;
use App\Services\EntryJsonImportResult;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shared result envelope for preview (read-only) and confirm (write) actions.
 */
final readonly class EntryJsonImportOutcome
{
    /**
     * @param  Collection<int, Entry>|null  $created
     */
    public function __construct(
        public EntryJsonImportResult $import,
        public ?Collection $created = null,
    ) {}

    public function successful(): bool
    {
        return $this->import->successful;
    }
}
