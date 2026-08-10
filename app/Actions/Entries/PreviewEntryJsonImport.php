<?php

declare(strict_types=1);

namespace App\Actions\Entries;

use App\Models\User;
use App\Services\EntryJsonImportService;

/**
 * Preview a JSON import: validate/map the payload, never write.
 */
final readonly class PreviewEntryJsonImport implements EntryJsonImportAction
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
        $import = $this->importService->prepare(
            $this->user,
            $this->entryTypeId,
            $this->payload,
        );

        return new EntryJsonImportOutcome(import: $import);
    }
}
