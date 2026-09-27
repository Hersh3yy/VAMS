<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use App\Services\EntryValidationService;
use Database\Seeders\ImageColorsSeeder;
use Illuminate\Validation\ValidationException;

// These exercise EntryValidationService directly (no HTTP), so they are
// unaffected by the web-CSRF issue that reddens the HTTP-level suites. They
// pin the `json` field type added for the Image Colors project and the
// three entry types the ImageColorsSeeder provisions.

beforeEach(function () {
    $this->service = new EntryValidationService;
});

/** Build a throwaway entry type with a given field_config. */
function entryTypeWith(array $fieldConfig): EntryType
{
    return EntryType::create([
        'name' => 'Probe '.uniqid(),
        'field_config' => $fieldConfig,
        'is_active' => true,
    ]);
}

it('passes a json field through unchanged, nested arrays intact', function () {
    $type = entryTypeWith([
        ['name' => 'colors', 'type' => 'json', 'label' => 'Colours', 'required' => true],
    ]);

    $palette = [
        ['name' => 'Red', 'hex' => '#FF0000', 'lab' => [53.24, 80.09, 67.2]],
        ['name' => 'Navy', 'hex' => '#000080', 'lab' => [12.97, 47.5, -64.7]],
    ];

    $validated = $this->service->validateContent($type, ['colors' => $palette]);

    expect($validated['colors'])->toBe($palette)
        ->and($validated['colors'][1]['lab'])->toBe([12.97, 47.5, -64.7]);
});

it('rejects a json field that is not an array', function () {
    $type = entryTypeWith([
        ['name' => 'colors', 'type' => 'json', 'label' => 'Colours', 'required' => true],
    ]);

    expect(fn () => $this->service->validateContent($type, ['colors' => 'not-an-array']))
        ->toThrow(ValidationException::class);
});

it('rejects a required json field that is missing', function () {
    $type = entryTypeWith([
        ['name' => 'colors', 'type' => 'json', 'label' => 'Colours', 'required' => true],
    ]);

    expect(fn () => $this->service->validateContent($type, []))
        ->toThrow(ValidationException::class);
});

it('validates scalar field types', function () {
    $type = entryTypeWith([
        ['name' => 'version', 'type' => 'number', 'label' => 'V', 'required' => true],
        ['name' => 'title', 'type' => 'text', 'label' => 'T', 'required' => true],
        ['name' => 'active', 'type' => 'checkbox', 'label' => 'A', 'required' => false],
    ]);

    $ok = $this->service->validateContent($type, ['version' => 3, 'title' => 'hi', 'active' => true]);
    expect($ok['version'])->toBe(3);

    expect(fn () => $this->service->validateContent($type, ['version' => 'NaN', 'title' => 'hi']))
        ->toThrow(ValidationException::class);
});

it('validates entry_relation against existing entry ids', function () {
    $user = User::factory()->create();
    $type = EntryType::create(['name' => 'Target', 'field_config' => [], 'is_active' => true]);
    $target = Entry::create([
        'user_id' => $user->id, 'entry_type_id' => $type->id,
        'title' => 'Target entry', 'content' => [], 'status' => 'draft',
    ]);

    $relType = entryTypeWith([
        ['name' => 'preset', 'type' => 'entry_relation', 'label' => 'Preset', 'required' => true, 'min' => 1, 'max' => 1],
    ]);

    $ok = $this->service->validateContent($relType, ['preset' => [$target->id]]);
    expect($ok['preset'])->toBe([$target->id]);

    expect(fn () => $this->service->validateContent($relType, ['preset' => ['00000000-0000-0000-0000-000000000000']]))
        ->toThrow(ValidationException::class);
});

it('validates a full processed-image payload against the seeded types', function () {
    // Seeds parent-colors, preset, processed-image + grants itamar (idempotent).
    $this->seed(ImageColorsSeeder::class);

    $user = User::where('email', 'itamar@gilboa.net')->firstOrFail();
    $service = $this->service;

    $presetType = EntryType::where('slug', 'preset')->firstOrFail();
    $pcType = EntryType::where('slug', 'parent-colors')->firstOrFail();
    $piType = EntryType::where('slug', 'processed-image')->firstOrFail();

    $preset = Entry::create([
        'user_id' => $user->id, 'entry_type_id' => $presetType->id,
        'title' => 'Rijksmuseum 2.8',
        'content' => $service->validateContent($presetType, ['institution' => 'Rijksmuseum']),
        'status' => 'draft',
    ]);

    $pc = Entry::create([
        'user_id' => $user->id, 'entry_type_id' => $pcType->id,
        'title' => 'Legacy CSS 32',
        'content' => $service->validateContent($pcType, [
            'version' => 0,
            'colors' => [['name' => 'Red', 'hex' => '#FF0000', 'lab' => [53.24, 80.09, 67.2]]],
        ]),
        'status' => 'draft',
    ]);

    $content = $service->validateContent($piType, [
        'preset' => [$preset->id],
        'parent_colors' => [$pc->id],
        'source_image_url' => 'https://bengijzel.ams3.digitaloceanspaces.com/image-colors/x.jpg',
        'colors' => [[
            'color' => '#8B0000', 'percentage' => 41.2,
            'parent' => ['name' => 'Navy', 'distance' => 30.3],
            'pantone' => ['code' => '19-1557', 'distance' => 2.1],
        ]],
        'analysis_settings' => ['k' => 13, 'sampleSize' => 10000, 'reproducibleRuns' => false],
        'average_confidence' => 72.5,
        'problematic_count' => 1,
    ]);

    $pi = Entry::create([
        'user_id' => $user->id, 'entry_type_id' => $piType->id,
        'title' => 'painting-001.jpg', 'content' => $content, 'status' => 'draft',
    ]);

    $reloaded = Entry::findOrFail($pi->id);
    expect($reloaded->content['colors'][0]['percentage'])->toBe(41.2)
        ->and($reloaded->content['preset'][0])->toBe($preset->id)
        ->and(Entry::find($reloaded->content['parent_colors'][0])->title)->toBe('Legacy CSS 32')
        ->and($user->entry_type_permissions)->toContain('processed-image');
});

it('stores a datetime field as the given ISO string, offset intact', function (): void {
    $type = entryTypeWith([
        ['name' => 'starts_at', 'type' => 'datetime', 'label' => 'Starts at', 'required' => true],
    ]);

    $validated = $this->service->validateContent($type, ['starts_at' => '2026-10-22T23:00:00+02:00']);

    expect($validated['starts_at'])->toBe('2026-10-22T23:00:00+02:00');
});

it('rejects a datetime field that is not a date', function (): void {
    $type = entryTypeWith([
        ['name' => 'starts_at', 'type' => 'datetime', 'label' => 'Starts at'],
    ]);

    expect(fn (): array => $this->service->validateContent($type, ['starts_at' => 'next thursday-ish']))
        ->toThrow(ValidationException::class);
});

it('allows an optional datetime field to be null', function (): void {
    $type = entryTypeWith([
        ['name' => 'starts_at', 'type' => 'datetime', 'label' => 'Starts at'],
    ]);

    expect($this->service->validateContent($type, ['starts_at' => null]))->toBe(['starts_at' => null]);
});

it('accepts a url field with a valid url', function (): void {
    $type = entryTypeWith([
        ['name' => 'tickets', 'type' => 'url', 'label' => 'Tickets', 'required' => true],
    ]);

    $validated = $this->service->validateContent($type, ['tickets' => 'https://www.amsterdam-dance-event.nl/en/program/2026/bret/']);

    expect($validated['tickets'])->toBe('https://www.amsterdam-dance-event.nl/en/program/2026/bret/');
});

it('rejects a url field that is not a url', function (): void {
    $type = entryTypeWith([
        ['name' => 'tickets', 'type' => 'url', 'label' => 'Tickets'],
    ]);

    expect(fn (): array => $this->service->validateContent($type, ['tickets' => 'not a url']))
        ->toThrow(ValidationException::class);
});
