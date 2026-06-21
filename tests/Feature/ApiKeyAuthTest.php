<?php

declare(strict_types=1);

use App\Models\User;

it('authenticates api requests with a valid plain-text key checked against its hash', function () {
    $user = User::factory()->create();
    $plainTextKey = $user->regenerateApiKey();

    expect($user->refresh()->api_key)->toBe(User::hashApiKey($plainTextKey));

    $this->withHeaders(['X-API-Key' => $plainTextKey])
        ->getJson('/api/test')
        ->assertSuccessful()
        ->assertJsonPath('user', $user->name);
});

it('rejects api requests without a key', function () {
    $this->getJson('/api/test')->assertUnauthorized();
});

it('rejects api requests with an invalid key', function () {
    User::factory()->create();

    $this->withHeaders(['X-API-Key' => 'not-a-real-key'])
        ->getJson('/api/test')
        ->assertUnauthorized();
});

it('rejects the stored hash itself as a key', function () {
    $user = User::factory()->create();
    $user->regenerateApiKey();

    $this->withHeaders(['X-API-Key' => $user->refresh()->api_key])
        ->getJson('/api/test')
        ->assertUnauthorized();
});

it('rejects api requests from unapproved users', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_approved' => false])->save();
    $plainTextKey = $user->regenerateApiKey();

    $this->withHeaders(['X-API-Key' => $plainTextKey])
        ->getJson('/api/test')
        ->assertForbidden();
});
