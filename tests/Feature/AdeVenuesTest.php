<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('geocodes each venue once, with district and ferry flag', function (): void {
    $owner = User::factory()->create(['email' => 'info@hiren.ninja']);
    $events = EntryType::create(['name' => 'ADE Event', 'slug' => 'ade-event', 'field_config' => [], 'is_active' => true]);
    $venues = EntryType::create(['name' => 'ADE Venue', 'slug' => 'ade-venue', 'field_config' => [], 'is_active' => true]);
    foreach ([['NDSM Warehouse', 'NDSM-plein 85, Amsterdam'], ['NDSM Warehouse', 'NDSM-plein 85, Amsterdam'], ['Felix Meritis', 'Keizersgracht 324, Amsterdam']] as [$venue, $address]) {
        Entry::create(['id' => (string) Str::uuid(), 'user_id' => $owner->id, 'entry_type_id' => $events->id, 'title' => 'X',
            'content' => ['venue' => $venue, 'address' => $address], 'status' => 'published', 'published_at' => now(), 'order' => 0]);
    }

    Http::fake([
        'api.pdok.nl/*NDSM*' => Http::response(['response' => ['docs' => [['type' => 'adres', 'centroide_ll' => 'POINT(4.8948 52.3994)', 'buurtnaam' => 'NDSM terrein', 'gemeentenaam' => 'Amsterdam']]]]),
        'api.pdok.nl/*' => Http::response(['response' => ['docs' => [['type' => 'adres', 'centroide_ll' => 'POINT(4.8843 52.3700)', 'buurtnaam' => 'Felix Meritisbuurt', 'gemeentenaam' => 'Amsterdam']]]]),
        'api.data.amsterdam.nl/*lat=52.3994*' => Http::response(['features' => [['properties' => ['display' => 'Noord']]]]),
        'api.data.amsterdam.nl/*' => Http::response(['features' => [['properties' => ['display' => 'Centrum']]]]),
    ]);

    $this->artisan('ade:venues')->assertSuccessful();
    $this->artisan('ade:venues')->assertSuccessful(); // nothing new the second time

    $saved = Entry::where('entry_type_id', $venues->id)->get()->keyBy('title');
    expect($saved)->toHaveCount(2)
        ->and($saved['NDSM Warehouse']->content)->toMatchArray(['area' => 'North', 'acrossIj' => true, 'neighbourhood' => 'NDSM terrein', 'lat' => 52.3994])
        ->and($saved['Felix Meritis']->content)->toMatchArray(['area' => 'Centre', 'acrossIj' => false]);
    Http::assertSentCount(4);
});
