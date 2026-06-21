<?php

declare(strict_types=1);

use App\Models\EntryType;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'entry_type_permissions' => ['project'],
    ]);

    $this->entryType = EntryType::query()->create([
        'name' => 'Project',
        'slug' => 'project',
        'field_config' => [
            ['name' => 'short_description', 'label' => 'Short Description', 'type' => 'textarea', 'required' => true],
            ['name' => 'year', 'label' => 'Year', 'type' => 'number', 'required' => true],
            ['name' => 'project_type', 'label' => 'Project Type', 'type' => 'select', 'required' => true, 'options' => ['website', 'webApplication']],
            ['name' => 'screenshots', 'label' => 'Screenshots', 'type' => 'image_collection', 'min' => 0, 'max' => 2],
        ],
        'is_active' => true,
    ]);
});

it('stores an entry whose content matches the field config', function () {
    $response = $this->actingAs($this->user)->post(route('entries.store'), [
        'title' => 'Doctor Mesi',
        'entry_type_id' => $this->entryType->id,
        'status' => 'published',
        'content' => [
            'short_description' => 'A clinic management app',
            'year' => 2024,
            'project_type' => 'webApplication',
            'screenshots' => [],
        ],
    ]);

    $response->assertRedirect(route('entries.index', ['type' => 'project']))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('entries', [
        'title' => 'Doctor Mesi',
        'entry_type_id' => $this->entryType->id,
    ]);
});

it('rejects select values that are not in the configured options', function () {
    $this->actingAs($this->user)->post(route('entries.store'), [
        'title' => 'Doctor Mesi',
        'entry_type_id' => $this->entryType->id,
        'content' => [
            'short_description' => 'A clinic management app',
            'year' => 2024,
            'project_type' => 'mobileApp',
        ],
    ])->assertSessionHasErrors(['project_type']);
});

it('rejects non-numeric values for number fields', function () {
    $this->actingAs($this->user)->post(route('entries.store'), [
        'title' => 'Doctor Mesi',
        'entry_type_id' => $this->entryType->id,
        'content' => [
            'short_description' => 'A clinic management app',
            'year' => 'twenty-twenty-four',
            'project_type' => 'website',
        ],
    ])->assertSessionHasErrors(['year']);
});

it('rejects image collections exceeding the configured max', function () {
    $this->actingAs($this->user)->post(route('entries.store'), [
        'title' => 'Doctor Mesi',
        'entry_type_id' => $this->entryType->id,
        'content' => [
            'short_description' => 'A clinic management app',
            'year' => 2024,
            'project_type' => 'website',
            'screenshots' => [
                ['path' => 'a.webp'],
                ['path' => 'b.webp'],
                ['path' => 'c.webp'],
            ],
        ],
    ])->assertSessionHasErrors(['screenshots']);
});

it('rejects missing required fields', function () {
    $this->actingAs($this->user)->post(route('entries.store'), [
        'title' => 'Doctor Mesi',
        'entry_type_id' => $this->entryType->id,
        'content' => [
            'year' => 2024,
        ],
    ])->assertSessionHasErrors(['short_description', 'project_type']);
});
