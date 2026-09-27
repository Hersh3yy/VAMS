<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeNewsType(): EntryType
{
    return EntryType::create([
        'name' => 'News',
        'slug' => 'news',
        'description' => 'News items',
        'field_config' => [
            [
                'name' => 'url',
                'type' => 'text',
                'label' => 'URL',
                'required' => false,
            ],
            [
                'name' => 'body',
                'type' => 'textarea',
                'label' => 'Body',
                'required' => true,
            ],
        ],
        'is_active' => true,
    ]);
}

it('previews json import without creating entries', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeNewsType();

    $response = $this->actingAs($user)->post(route('entries.preview-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => [
            [
                'title' => 'Opening night',
                'content' => [
                    'url' => 'https://example.com',
                    'body' => 'We opened.',
                ],
            ],
            [
                'title' => 'Second item',
                'content' => ['body' => 'More news'],
                'status' => 'draft',
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Entries/JsonPreview')
            ->where('entryType.slug', 'news')
            ->has('entries', 2)
            ->where('entries.0.title', 'Opening night')
            ->where('entries.1.status', 'draft')
            ->has('payload')
        );

    expect(Entry::query()->count())->toBe(0);
});

it('creates entries only after confirm from preview payload', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeNewsType();

    $payload = [
        'title' => 'Opening night',
        'content' => [
            'url' => 'https://example.com',
            'body' => 'We opened.',
        ],
    ];

    $this->actingAs($user)->post(route('entries.preview-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => $payload,
    ])->assertSuccessful();

    expect(Entry::query()->count())->toBe(0);

    $response = $this->actingAs($user)->post(route('entries.store-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => $payload,
    ]);

    $response->assertRedirect(route('entries.index', ['type' => 'news']))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'title' => 'Opening night',
        'entry_type_id' => $entryType->id,
        'status' => 'published',
    ]);
});

it('creates many entries from a json array payload', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeNewsType();

    $response = $this->actingAs($user)->post(route('entries.store-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => [
            [
                'title' => 'First',
                'content' => ['body' => 'One'],
            ],
            [
                'title' => 'Second',
                'content' => ['body' => 'Two'],
                'status' => 'draft',
            ],
            [
                'title' => 'Third',
                'url' => 'https://flat.example',
                'body' => 'Flat fields work',
            ],
        ],
    ]);

    $response->assertRedirect(route('entries.index', ['type' => 'news']))
        ->assertSessionHas('success', '3 entries created successfully');

    expect(Entry::query()->where('user_id', $user->id)->count())->toBe(3);

    $second = Entry::query()->where('title', 'Second')->first();
    expect($second->status)->toBe('draft')
        ->and($second->published_at)->toBeNull();

    $third = Entry::query()->where('title', 'Third')->first();
    expect($third->content['body'])->toBe('Flat fields work')
        ->and($third->content['url'])->toBe('https://flat.example');
});

it('rejects invalid content on preview and inserts nothing', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeNewsType();

    $response = $this->actingAs($user)->from(route('entries.create', ['type' => 'news']))->post(route('entries.preview-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => [
            [
                'title' => 'Good',
                'content' => ['body' => 'ok'],
            ],
            [
                'title' => 'Bad',
                'content' => ['url' => 'https://missing-body.test'],
            ],
        ],
    ]);

    $response->assertRedirect(route('entries.create', ['type' => 'news']));
    $response->assertSessionHasErrors('entries.1.content.body');

    expect(Entry::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('returns to the create json editor with the drafted payload', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'pro',
    ]);
    $entryType = makeNewsType();

    $payload = [
        ['title' => 'Drafted', 'content' => ['body' => 'keep me']],
    ];

    $response = $this->actingAs($user)->post(route('entries.edit-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => $payload,
    ]);

    $response->assertRedirect(route('entries.create', ['type' => 'news']))
        ->assertSessionHas('jsonImportDraft', [
            'mode' => 'json',
            'payload' => $payload,
        ]);
});

it('prevents unauthorized entry types on preview', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['other'],
        'plan' => 'pro',
    ]);
    $entryType = makeNewsType();

    $response = $this->actingAs($user)->post(route('entries.preview-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => [
            'title' => 'Nope',
            'content' => ['body' => 'denied'],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
    expect(Entry::query()->count())->toBe(0);
});

it('enforces plan limits before preview', function (): void {
    $user = User::factory()->create([
        'entry_type_permissions' => ['news'],
        'plan' => 'free',
    ]);
    $entryType = makeNewsType();

    for ($i = 0; $i < 99; $i++) {
        Entry::create([
            'user_id' => $user->id,
            'entry_type_id' => $entryType->id,
            'title' => "Existing {$i}",
            'content' => ['body' => 'x'],
            'status' => 'published',
            'published_at' => now(),
            'order' => $i,
        ]);
    }

    $response = $this->actingAs($user)->from(route('entries.create', ['type' => 'news']))->post(route('entries.preview-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => [
            ['title' => 'One more ok conceptually', 'content' => ['body' => 'a']],
            ['title' => 'This tips over', 'content' => ['body' => 'b']],
        ],
    ]);

    $response->assertRedirect(route('entries.create', ['type' => 'news']));
    $response->assertSessionHas('error');

    expect(Entry::query()->where('user_id', $user->id)->count())->toBe(99);
});

it('requires guests to authenticate for preview and store', function (): void {
    $entryType = makeNewsType();

    $this->post(route('entries.preview-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => ['title' => 'Guest', 'content' => ['body' => 'nope']],
    ])->assertRedirect(route('login'));

    $this->post(route('entries.store-json'), [
        'entry_type_id' => $entryType->id,
        'payload' => ['title' => 'Guest', 'content' => ['body' => 'nope']],
    ])->assertRedirect(route('login'));
});
