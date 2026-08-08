<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\Mosaic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_approved' => true,
        'email_verified_at' => now(),
        'plan' => 'free',
    ]);
});

it('blocks album creation once the plan limit is reached', function () {
    Album::factory()->count(10)->forUser($this->user)->create();

    $this->actingAs($this->user);

    $response = $this->post(route('albums.store'), [
        'title' => 'One too many',
        'description' => 'Should be blocked',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('albums', ['title' => 'One too many']);
});

it('allows album creation when under the plan limit', function () {
    Album::factory()->count(9)->forUser($this->user)->create();

    $this->actingAs($this->user);

    $response = $this->post(route('albums.store'), [
        'title' => 'Still room',
        'description' => 'Should succeed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('albums', ['title' => 'Still room']);
});

it('blocks mosaic creation once the plan limit is reached', function () {
    Mosaic::factory()->count(5)->forUser($this->user)->create();

    $this->actingAs($this->user);

    $response = $this->post(route('mosaics.store'), [
        'title' => 'One too many',
        'description' => 'Should be blocked',
        'columns' => 3,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('mosaics', ['title' => 'One too many']);
});

it('has no album/mosaic/entry limits on the pro plan', function () {
    $this->user->update(['plan' => 'pro']);
    Album::factory()->count(25)->forUser($this->user)->create();

    $this->actingAs($this->user);

    $response = $this->post(route('albums.store'), [
        'title' => 'Unlimited plan album',
        'description' => 'Should succeed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('albums', ['title' => 'Unlimited plan album']);
});
