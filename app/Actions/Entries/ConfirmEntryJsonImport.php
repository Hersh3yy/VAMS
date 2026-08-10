<?php

declare(strict_types=1);

namespace App\Actions\Entries;

use App\Models\User;
use App\Services\EntryJsonImportService;

/**
 * Confirm a JSON import: re-validate then persist.
 */
final readonly class ConfirmEntryJsonImport implements EntryJsonImportAction
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

    public function execute(): EntryJsonImportOutcome
    {
        ['result' => $import, 'created' => $created] = $this->importService->commit(
            $this->user,
            $this->entryTypeId,
            $this->payload,
        );

        return new EntryJsonImportOutcome(
            import: $import,
            created: $created,
        );
    }
}
