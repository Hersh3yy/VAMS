<?php

declare(strict_types=1);

namespace App\Commands\EntryJson;

use App\Models\User;
use App\Services\EntryJsonImportService;

/**
 * Concrete Command: re-validate then persist the JSON import.
 *
 * @see https://refactoring.guru/design-patterns/command
 */
final readonly class ConfirmEntryJsonImportCommand implements EntryJsonCommand
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
        ['result' => $import, 'created' => $created] = $this->importService->commit(
            $this->user,
            $this->entryTypeId,
            $this->payload,
        );

        return new EntryJsonCommandOutcome(
            import: $import,
            created: $created,
        );
    }
}
