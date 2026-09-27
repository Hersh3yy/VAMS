<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;

pest()->skip('Abandoned: Playwright e2e hangs on Inertia login. Feature tests cover this.');

it('previews and confirms a JSON entry import in the browser', function (): void {
    EntryType::create([
        'name' => 'News',
        'slug' => 'news-browser',
        'field_config' => [
            [
                'name' => 'body',
                'type' => 'textarea',
                'label' => 'Body',
                'required' => true,
            ],
        ],
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'is_approved' => true,
        'email_verified_at' => now(),
        'entry_type_permissions' => ['news-browser'],
        'plan' => 'pro',
    ]);

    $payload = json_encode([
        'title' => 'Browser import headline',
        'content' => [
            'body' => 'Imported via Pest browser e2e.',
        ],
    ], JSON_THROW_ON_ERROR);

    visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('form button')
        ->assertSee('Welcome')
        ->visit('/entries/create?type=news-browser')
        ->assertSee('Create New News')
        ->click('Insert JSON')
        ->assertSee('Review entries')
        ->fill('#entry-json-payload', $payload)
        ->click('Review entries')
        ->assertSee('Review import')
        ->assertSee('Browser import headline')
        ->assertSee('Nothing has been created yet')
        ->click('Confirm & create 1 entry')
        ->assertSee('Browser import headline');

    expect(Entry::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Entry::query()->where('title', 'Browser import headline')->exists())->toBeTrue();
});
