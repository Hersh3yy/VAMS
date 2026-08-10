<?php

declare(strict_types=1);

namespace App\Services;

use App\Adapters\AdaptedEntry;
use App\Adapters\EntryJsonPayloadAdapter;
use App\Models\EntryType;
use App\Models\User;
use App\Services\Plans\PlanLimitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Facade over the JSON entry-import subsystem.
 *
 * Clients (controllers, later commands) call prepare() / commit() instead of
 * wiring Adapter + validation + plan limits + EntryService themselves.
 *
 * @see https://refactoring.guru/design-patterns/facade
 */
final class EntryJsonImportService
{
    private const MAX_ENTRIES = 100;

    public function __construct(
        private readonly EntryJsonPayloadAdapter $adapter,
        private readonly EntryValidationService $validationService,
        private readonly PlanLimitService $planLimitService,
        private readonly EntryService $entryService,
    ) {}

    /**
     * Adapt + authorize + validate. Never writes. Never returns HTTP types.
     *
     * @param  array<mixed>  $payload
     */
    public function prepare(User $user, string $entryTypeId, array $payload): EntryJsonImportResult
    {
        $entryType = EntryType::query()->find($entryTypeId);

        if (! $entryType instanceof EntryType) {
            return EntryJsonImportResult::invalid([
                'entry_type_id' => ['The selected entry type does not exist.'],
            ]);
        }

        if (! $user->hasEntryTypePermission($entryType->slug)) {
            return EntryJsonImportResult::invalid([
                'error' => ['You do not have permission to create entries of this type.'],
            ]);
        }

        try {
            $adapted = $this->adapter->adapt(
                $payload,
                $entryType->field_config ?? [],
            );
        } catch (InvalidArgumentException $e) {
            return EntryJsonImportResult::invalid([
                'payload' => [$e->getMessage()],
            ]);
        }

        if (count($adapted) > self::MAX_ENTRIES) {
            return EntryJsonImportResult::invalid([
                'payload' => ['You may insert at most '.self::MAX_ENTRIES.' entries at a time.'],
            ]);
        }

        $remaining = $this->planLimitService->remaining($user, 'entries');

        if ($remaining !== null && count($adapted) > $remaining) {
            return EntryJsonImportResult::flashFailure(
                $this->planLimitService->limitMessage($user, 'entries')
                .' You tried to insert '.count($adapted).' but only '.$remaining.' remain.'
            );
        }

        $validated = [];

        foreach ($adapted as $index => $entry) {
            try {
                $content = $this->validationService->validateContent($entryType, $entry->content);
            } catch (ValidationException $e) {
                $prefixed = [];

                foreach ($e->errors() as $field => $messages) {
                    $prefixed["entries.{$index}.content.{$field}"] = $messages;
                }

                return EntryJsonImportResult::invalid($prefixed);
            }

            if (strlen($entry->title) > 255) {
                return EntryJsonImportResult::invalid([
                    "entries.{$index}.title" => ['The entry title cannot be longer than 255 characters.'],
                ]);
            }

            $validated[] = $entry->withContent($content);
        }

        return EntryJsonImportResult::success($entryType, $validated, $payload);
    }

    /**
     * Persist previously prepared entries (re-runs prepare for safety).
     *
     * @param  array<mixed>  $payload
     * @return array{result: EntryJsonImportResult, created: Collection<int, \App\Models\Entry>|null}
     */
    public function commit(User $user, string $entryTypeId, array $payload): array
    {
        $result = $this->prepare($user, $entryTypeId, $payload);

        if (! $result->successful) {
            return ['result' => $result, 'created' => null];
        }

        /** @var EntryType $entryType */
        $entryType = $result->entryType;

        /** @var list<AdaptedEntry> $entries */
        $entries = $result->entries;

        $created = $this->entryService->createManyForUser(
            $user,
            $entryType,
            array_map(static fn (AdaptedEntry $entry): array => $entry->toArray(), $entries),
        );

        return ['result' => $result, 'created' => $created];
    }
}
