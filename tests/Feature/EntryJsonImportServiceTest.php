<?php

declare(strict_types=1);

use App\Data\ImportEntryData;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use App\Services\EntryJsonImportService;

function makeImportNewsType(): EntryType
{
    return EntryType::create([
        'name' => 'News',
        'slug' => 'news',
        'field_config' => [
            ['name' => 'body', 'type' => 'textarea', 'label' => 'Body', 'required' => true],
        ],
        'is_active' => true,
    ]);
}

it('prepares valid payloads without writing entries', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeImportNewsType();
    $service = app(EntryJsonImportService::class);

    $result = $service->prepare($user, $entryType->id, [
        'title' => 'Preview only',
        'content' => ['body' => 'Hello'],
    ]);

    expect($result->successful)->toBeTrue()
        ->and($result->entries)->toHaveCount(1)
        ->and($result->entries[0])->toBeInstanceOf(ImportEntryData::class)
        ->and(Entry::query()->count())->toBe(0);
});

it('commits prepared payloads by creating entries', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeImportNewsType();
    $service = app(EntryJsonImportService::class);

    ['result' => $result, 'created' => $created] = $service->commit($user, $entryType->id, [
        ['title' => 'One', 'content' => ['body' => 'A']],
        ['title' => 'Two', 'content' => ['body' => 'B']],
    ]);

    expect($result->successful)->toBeTrue()
        ->and($created)->toHaveCount(2)
        ->and(Entry::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('returns field errors without creating anything', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeImportNewsType();
    $service = app(EntryJsonImportService::class);

    $result = $service->prepare($user, $entryType->id, [
        'title' => 'Bad',
        'content' => [],
    ]);

    expect($result->successful)->toBeFalse()
        ->and($result->errors)->not->toBeEmpty()
        ->and(collect($result->errors)->keys()->implode(' '))->toContain('body')
        ->and(Entry::query()->count())->toBe(0);
});
