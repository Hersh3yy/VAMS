<?php

declare(strict_types=1);

use App\Commands\EntryJson\ConfirmEntryJsonImportCommand;
use App\Commands\EntryJson\PreviewEntryJsonImportCommand;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use App\Services\EntryJsonImportService;

function makeCommandNewsType(): EntryType
{
    return EntryType::create([
        'name' => 'News',
        'slug' => 'news-cmd',
        'field_config' => [
            ['name' => 'body', 'type' => 'textarea', 'label' => 'Body', 'required' => true],
        ],
        'is_active' => true,
    ]);
}

it('preview command validates without writing', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news-cmd'],
        'plan' => 'pro',
    ]);
    $entryType = makeCommandNewsType();

    $outcome = (new PreviewEntryJsonImportCommand(
        app(EntryJsonImportService::class),
        $user,
        $entryType->id,
        ['title' => 'Preview cmd', 'content' => ['body' => 'x']],
    ))->execute();

    expect($outcome->successful())->toBeTrue()
        ->and($outcome->created)->toBeNull()
        ->and(Entry::query()->count())->toBe(0);
});

it('confirm command persists entries', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news-cmd'],
        'plan' => 'pro',
    ]);
    $entryType = makeCommandNewsType();

    $outcome = (new ConfirmEntryJsonImportCommand(
        app(EntryJsonImportService::class),
        $user,
        $entryType->id,
        [
            ['title' => 'A', 'content' => ['body' => '1']],
            ['title' => 'B', 'content' => ['body' => '2']],
        ],
    ))->execute();

    expect($outcome->successful())->toBeTrue()
        ->and($outcome->created)->toHaveCount(2)
        ->and(Entry::query()->where('user_id', $user->id)->count())->toBe(2);
});
