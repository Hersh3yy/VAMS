<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->forceFill(['is_admin' => true])->save();
});

it('forbids non-admin users from managing entry types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.entry-types.store'), [
            'name' => 'Project',
            'field_config' => [
                ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
            ],
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('entry_types', ['slug' => 'project']);
});

it('creates an entry type from a valid field config', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.entry-types.store'), [
        'name' => 'Project',
        'description' => 'Portfolio projects',
        'field_config' => [
            ['name' => 'short_description', 'label' => 'Short Description', 'type' => 'textarea', 'required' => true],
            ['name' => 'year', 'label' => 'Year', 'type' => 'number', 'required' => true],
            ['name' => 'project_type', 'label' => 'Project Type', 'type' => 'select', 'required' => true, 'options' => ['website', 'webApplication']],
            ['name' => 'screenshots', 'label' => 'Screenshots', 'type' => 'image_collection', 'min' => 0, 'max' => 12],
        ],
    ]);

    $response->assertRedirect(route('admin.entry-types.index'))
        ->assertSessionHas('success');

    $entryType = EntryType::query()->where('slug', 'project')->firstOrFail();

    expect($entryType->field_config)->toHaveCount(4)
        ->and($entryType->is_active)->toBeTrue();
});

it('rejects unsupported field types', function () {
    $this->actingAs($this->admin)->post(route('admin.entry-types.store'), [
        'name' => 'Project',
        'field_config' => [
            ['name' => 'url', 'label' => 'URL', 'type' => 'url', 'required' => true],
        ],
    ])->assertSessionHasErrors(['field_config.0.type']);
});

it('rejects duplicate field names within a config', function () {
    $this->actingAs($this->admin)->post(route('admin.entry-types.store'), [
        'name' => 'Project',
        'field_config' => [
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'text', 'required' => true],
            ['name' => 'summary', 'label' => 'Summary Again', 'type' => 'textarea', 'required' => false],
        ],
    ])->assertSessionHasErrors(['field_config.0.name', 'field_config.1.name']);
});

it('rejects select fields without options', function () {
    $this->actingAs($this->admin)->post(route('admin.entry-types.store'), [
        'name' => 'Project',
        'field_config' => [
            ['name' => 'project_type', 'label' => 'Project Type', 'type' => 'select', 'required' => true],
        ],
    ])->assertSessionHasErrors(['field_config.0.options']);
});

it('updates an entry type', function () {
    $entryType = EntryType::query()->create([
        'name' => 'Project',
        'slug' => 'project',
        'field_config' => [
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
        ],
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->patch(route('admin.entry-types.update', $entryType), [
        'name' => 'Project',
        'field_config' => [
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
            ['name' => 'year', 'label' => 'Year', 'type' => 'number', 'required' => false],
        ],
        'is_active' => false,
    ]);

    $response->assertRedirect(route('admin.entry-types.index'));

    $entryType->refresh();

    expect($entryType->field_config)->toHaveCount(2)
        ->and($entryType->is_active)->toBeFalse();
});

it('refuses to delete an entry type that has entries', function () {
    $user = User::factory()->create();

    $entryType = EntryType::query()->create([
        'name' => 'Project',
        'slug' => 'project',
        'field_config' => [
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
        ],
        'is_active' => true,
    ]);

    Entry::query()->create([
        'user_id' => $user->id,
        'entry_type_id' => $entryType->id,
        'title' => 'A project',
        'content' => ['summary' => 'Hello'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.entry-types.destroy', $entryType))
        ->assertRedirect(route('admin.entry-types.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('entry_types', ['id' => $entryType->id]);
});

it('deletes an entry type without entries', function () {
    $entryType = EntryType::query()->create([
        'name' => 'Project',
        'slug' => 'project',
        'field_config' => [
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => true],
        ],
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.entry-types.destroy', $entryType))
        ->assertRedirect(route('admin.entry-types.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('entry_types', ['id' => $entryType->id]);
});
