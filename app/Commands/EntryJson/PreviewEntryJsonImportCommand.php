<?php

declare(strict_types=1);

namespace App\Commands\EntryJson;

use App\Models\User;
use App\Services\EntryJsonImportService;

/**
 * Concrete Command: validate/adapt the payload and return a previewable result.
 * Never writes to the database.
 *
 * @see https://refactoring.guru/design-patterns/command
 */
final readonly class PreviewEntryJsonImportCommand implements EntryJsonCommand
{
    /**
     * @param  array<mixed>  $payload
     */
    public function __construct(
        private EntryJsonImportService $importService,
        private User $user,
        private string $entryTypeId,
        private array $payload,
    ) {}

    public function execute(): EntryJsonCommandOutcome
    {
        $import = $this->importService->prepare(
            $this->user,
            $this->entryTypeId,
            $this->payload,
        );

        return new EntryJsonCommandOutcome(import: $import);
    }
}
