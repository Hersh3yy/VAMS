<?php

declare(strict_types=1);

use App\Models\Album;
use App\Models\User;
use App\Services\Plans\Plan;
use App\Services\Plans\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(PlanLimitService::class);
});

it('resolves the free plan by default', function (): void {
    $user = User::factory()->create();

    expect($user->plan())->toBeInstanceOf(Plan::class)
        ->and($user->plan()->slug)->toBe('free')
        ->and($user->plan()->limit('albums'))->toBe(10);
});

it('falls back to the default plan for an unknown slug', function (): void {
    $user = User::factory()->create(['plan' => 'nonexistent']);

    expect($user->plan()->slug)->toBe(config('plans.default'));
});

it('reports unlimited remaining quota for a plan with a null limit', function (): void {
    $user = User::factory()->create(['plan' => 'pro']);

    expect($this->service->remaining($user, 'albums'))->toBeNull()
        ->and($this->service->hasReached($user, 'albums'))->toBeFalse();
});

it('counts current usage against the plan limit', function (): void {
    $user = User::factory()->create(['plan' => 'free']);
    Album::factory()->count(3)->forUser($user)->create();

    expect($this->service->remaining($user, 'albums'))->toBe(7)
        ->and($this->service->hasReached($user, 'albums'))->toBeFalse();
});

it('reports the limit as reached once usage meets the cap', function (): void {
    $user = User::factory()->create(['plan' => 'free']);
    Album::factory()->count(10)->forUser($user)->create();

    expect($this->service->hasReached($user, 'albums'))->toBeTrue()
        ->and($this->service->limitMessage($user, 'albums'))->toContain('10 albums');
});

it('throws for an unregistered resource key', function (): void {
    $user = User::factory()->create();

    $this->service->remaining($user, 'not-a-real-resource');
})->throws(InvalidArgumentException::class);
