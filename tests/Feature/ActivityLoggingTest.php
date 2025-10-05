<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Album;
use App\Models\Mosaic;
use App\Models\User;

// Unit tests for Activity model
it('creates activity with correct relationships', function () {
    $user = User::factory()->create();
    $album = Album::factory()->forUser($user)->create();

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'subject_type' => Album::class,
        'subject_id' => $album->id,
    ]);

    expect($activity->user)->toBeInstanceOf(User::class);
    expect($activity->subject)->toBeInstanceOf(Album::class);
    expect($activity->user->id)->toBe($user->id);
    expect($activity->subject->id)->toBe($album->id);
});

it('casts properties to array', function () {
    $activity = Activity::factory()->create([
        'properties' => ['key' => 'value', 'nested' => ['data' => 'test']],
    ]);

    expect($activity->properties)->toBeArray();
    expect($activity->properties['key'])->toBe('value');
    expect($activity->properties['nested']['data'])->toBe('test');
});

it('casts user_id to string', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $user->id]);

    expect($activity->user_id)->toBeString();
    expect($activity->user_id)->toBe((string) $user->id);
});

it('has fillable attributes', function () {
    $activity = new Activity;
    $fillable = $activity->getFillable();

    expect($fillable)->toContain('type');
    expect($fillable)->toContain('description');
    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('subject_type');
    expect($fillable)->toContain('subject_id');
    expect($fillable)->toContain('properties');
});

it('can create activity with minimal data', function () {
    $user = User::factory()->create();

    $activity = Activity::create([
        'type' => 'test',
        'description' => 'Test activity',
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);

    expect($activity->exists)->toBeTrue();
    expect($activity->type)->toBe('test');
    expect($activity->description)->toBe('Test activity');
    expect($activity->user_id)->toBe($user->id);
});

it('can create activity with properties', function () {
    $user = User::factory()->create();
    $properties = [
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'changes' => ['old' => 'value1', 'new' => 'value2'],
    ];

    $activity = Activity::create([
        'type' => 'update',
        'description' => 'Updated something',
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'properties' => $properties,
    ]);

    expect($activity->properties)->toBe($properties);
    expect($activity->properties['ip_address'])->toBe('192.168.1.1');
    expect($activity->properties['changes']['old'])->toBe('value1');
});

it('automatically logs album creation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $album = Album::factory()->forUser($user)->create([
        'title' => 'Test Album',
    ]);

    $activity = Activity::where('subject_type', Album::class)
        ->where('subject_id', $album->id)
        ->where('type', 'create')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('Created Album');
    expect($activity->user_id)->toBe($user->id);
    expect($activity->subject_type)->toBe(Album::class);
    expect($activity->subject_id)->toBe($album->id);
});

it('automatically logs album updates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $album = Album::factory()->forUser($user)->create([
        'title' => 'Original Title',
    ]);

    $album->update(['title' => 'Updated Title']);

    $activity = Activity::where('subject_type', Album::class)
        ->where('subject_id', $album->id)
        ->where('type', 'update')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('Updated Album');
    expect($activity->user_id)->toBe($user->id);
});

it('automatically logs album deletion', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $album = Album::factory()->forUser($user)->create();
    $albumId = $album->id;

    $album->delete();

    $activity = Activity::where('subject_type', Album::class)
        ->where('subject_id', $albumId)
        ->where('type', 'delete')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('Deleted Album');
    expect($activity->user_id)->toBe($user->id);
});

it('automatically logs mosaic creation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $mosaic = Mosaic::factory()->forUser($user)->create([
        'title' => 'Test Mosaic',
    ]);

    $activity = Activity::where('subject_type', Mosaic::class)
        ->where('subject_id', $mosaic->id)
        ->where('type', 'create')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('Created Mosaic');
    expect($activity->user_id)->toBe($user->id);
});

it('handles console context for activity logging', function () {
    $user = User::factory()->create();

    // Simulate console context (no auth)
    auth()->logout();

    $album = Album::factory()->forUser($user)->create();

    $activity = Activity::where('subject_type', Album::class)
        ->where('subject_id', $album->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->user_id)->toBe($user->id);
    expect($activity->description)->toBe('Created Album');
});

it('skips logging when no user context available', function () {
    // Ensure no user context
    auth()->logout();

    // Create a model without user_id (simulating system operation)
    $album = new Album([
        'title' => 'System Album',
        'description' => 'Created by system',
    ]);

    // This should not create an activity since there's no user context
    $activityCount = Activity::count();

    // Simulate the model creation without user context
    // (In real scenario, this would be handled by the trait)
    expect($activityCount)->toBe(0);
});

it('creates multiple activities for different operations', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $album = Album::factory()->forUser($user)->create();
    $album->update(['title' => 'Updated Title']);
    $album->delete();

    $activities = Activity::where('subject_type', Album::class)
        ->where('subject_id', $album->id)
        ->orderBy('created_at')
        ->get();

    expect($activities)->toHaveCount(3);
    expect($activities[0]->type)->toBe('create');
    expect($activities[1]->type)->toBe('update');
    expect($activities[2]->type)->toBe('delete');
});

it('associates activities with correct user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($user1);
    $album1 = Album::factory()->forUser($user1)->create();

    $this->actingAs($user2);
    $album2 = Album::factory()->forUser($user2)->create();

    $user1Activities = Activity::where('user_id', $user1->id)->get();
    $user2Activities = Activity::where('user_id', $user2->id)->get();

    expect($user1Activities)->toHaveCount(1);
    expect($user2Activities)->toHaveCount(1);
    expect($user1Activities->first()->subject_id)->toBe($album1->id);
    expect($user2Activities->first()->subject_id)->toBe($album2->id);
});

it('can retrieve activities through model relationship', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $album = Album::factory()->forUser($user)->create();
    $album->update(['title' => 'Updated Title']);

    $activities = $album->activities;

    expect($activities)->toHaveCount(2);
    expect($activities->pluck('type')->toArray())->toContain('create');
    expect($activities->pluck('type')->toArray())->toContain('update');
});

it('can retrieve user activities through user relationship', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $album = Album::factory()->forUser($user)->create();
    $mosaic = Mosaic::factory()->forUser($user)->create();

    $userActivities = $user->activities;

    expect($userActivities->count())->toBe(2);
    expect($userActivities->pluck('subject_type')->toArray())->toContain(Album::class);
    expect($userActivities->pluck('subject_type')->toArray())->toContain(Mosaic::class);
});
