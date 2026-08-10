<?php

declare(strict_types=1);

namespace App\Commands\EntryJson;

/**
 * Command interface: a request turned into an object that can be executed later.
 *
 * @see https://refactoring.guru/design-patterns/command
 */
interface EntryJsonCommand
{
    public function execute(): EntryJsonCommandOutcome;
}
