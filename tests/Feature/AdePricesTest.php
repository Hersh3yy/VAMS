<?php

declare(strict_types=1);

use App\Console\Commands\AdePrices;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('reads the lowest ticket on sale from Weeztix and Resident Advisor', function (): void {
    $owner = User::factory()->create(['email' => 'info@hiren.ninja']);
    $type = EntryType::create(['name' => 'ADE Event', 'slug' => 'ade-event', 'field_config' => [], 'is_active' => true]);
    $make = fn (string $title, string $url, string $status = 'available'): Entry => Entry::create([
        'id' => (string) Str::uuid(), 'user_id' => $owner->id, 'entry_type_id' => $type->id, 'title' => $title,
        'content' => ['ticketUrl' => $url, 'ticketStatus' => $status, 'startsAt' => '2026-10-22T22:00:00+02:00'],
        'status' => 'published', 'published_at' => now(), 'order' => 0,
    ]);
    $weeztix = $make('Weeztix night', 'https://shop.weeztix.com/b2abd21f-55d2-4bd1-ba76-3662c4131095/tickets');
    $ra = $make('RA night', 'https://nl.ra.co/events/2466273');
    $other = $make('Elsewhere', 'https://www.ticketmaster.nl/event/1');
    $soldOut = $make('Sold out', 'https://ra.co/events/1', 'sold out');

    Http::fake([
        'shop.api.openticket.tech/*' => Http::response([
            'events' => [
                ['guid' => 'thu', 'start' => '2026-10-22T20:00:00+00:00'],
                ['guid' => 'fri', 'start' => '2026-10-23T20:00:00+00:00'],
            ],
            'tickets' => [
                'a' => ['event_id' => 'thu', 'min_price' => 2250, 'status' => 'available'],
                'b' => ['event_id' => 'thu', 'min_price' => 1500, 'status' => 'sold_out'],
                'c' => ['event_id' => 'thu', 'min_price' => 0, 'status' => 'available', 'hide_without_coupon' => true],
                'd' => ['event_id' => 'fri', 'min_price' => 1000, 'status' => 'available'],
            ],
        ]),
        'ra.co/graphql' => Http::response(['data' => ['event' => ['cost' => '15€', 'tickets' => [
            ['priceRetail' => 10, 'validType' => 'NOLONGERONSALE', 'isAddOn' => false],
            ['priceRetail' => 15, 'validType' => 'VALID', 'isAddOn' => false],
            ['priceRetail' => 5, 'validType' => 'VALID', 'isAddOn' => true],
        ]]]]),
    ]);

    $this->artisan('ade:prices')->assertSuccessful();
    $this->artisan('ade:prices')->assertSuccessful(); // checked recently: nothing to do

    expect($weeztix->fresh()->content)->toMatchArray(['priceFrom' => 22.5, 'priceSource' => 'weeztix'])
        ->and($ra->fresh()->content)->toMatchArray(['priceFrom' => 15, 'priceSource' => 'ra'])
        ->and($other->fresh()->content)->not->toHaveKey('priceCheckedAt')
        ->and($soldOut->fresh()->content)->not->toHaveKey('priceCheckedAt');
    Http::assertSentCount(2);
});

it('understands shop links and listed costs', function (): void {
    expect(AdePrices::shopOf('https://eur01.safelinks.protection.outlook.com/?url=https%3A%2F%2Fweeztix.shop%2Fngzb5zq5&data=x'))->toBe(['weeztix-short', 'https://weeztix.shop/ngzb5zq5'])
        ->and(AdePrices::shopOf('https://ra.co/events/123?ref=x'))->toBe(['ra', '123'])
        ->and(AdePrices::shopOf('https://shotgun.live/events/x'))->toBeNull()
        ->and(AdePrices::parseCost('€12,50 - €20'))->toBe(12.5)
        ->and(AdePrices::parseCost('Free before 23:00'))->toBe(0.0)
        ->and(AdePrices::parseCost('TBA'))->toBeNull();
});
