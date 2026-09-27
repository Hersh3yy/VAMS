<?php

declare(strict_types=1);

use App\Actions\Entries\ConfirmEntryJsonImport;
use App\Actions\Entries\PreviewEntryJsonImport;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use App\Services\EntryJsonImportService;

function makeActionNewsType(): EntryType
{
    return EntryType::create([
        'name' => 'News',
        'slug' => 'news-action',
        'field_config' => [
            ['name' => 'body', 'type' => 'textarea', 'label' => 'Body', 'required' => true],
        ],
        'is_active' => true,
    ]);
}

it('preview action validates without writing', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news-action'],
        'plan' => 'pro',
    ]);
    $entryType = makeActionNewsType();

    $outcome = (new PreviewEntryJsonImport(
        app(EntryJsonImportService::class),
        $user,
        $entryType->id,
        ['title' => 'Preview action', 'content' => ['body' => 'x']],
    ))->execute();

    expect($outcome->successful())->toBeTrue()
        ->and($outcome->created)->toBeNull()
        ->and(Entry::query()->count())->toBe(0);
});

it('confirm action persists entries', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news-action'],
        'plan' => 'pro',
    ]);
    $entryType = makeActionNewsType();

    $outcome = (new ConfirmEntryJsonImport(
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
