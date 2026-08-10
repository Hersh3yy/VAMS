<?php

declare(strict_types=1);

namespace App\Actions\Entries;

/**
 * Single-purpose application action for entry JSON import intents.
 * (GoF Command pattern; named Action to avoid confusion with Artisan commands.)
 *
 * @see https://refactoring.guru/design-patterns/command
 */
interface EntryJsonImportAction
{
    public function execute(): EntryJsonImportOutcome;
}
