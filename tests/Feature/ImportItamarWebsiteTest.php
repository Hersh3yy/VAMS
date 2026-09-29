<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\User;
use Database\Seeders\ItamarWebsiteSeeder;

beforeEach(function (): void {
    $this->seed(ItamarWebsiteSeeder::class);
    $this->itamar = User::where('email', 'itamar@gilboa.net')->firstOrFail();
});

it('imports every Strapi item once, even when run twice', function (): void {
    $this->artisan('strapi:import-itamar-website')->assertSuccessful();
    $this->artisan('strapi:import-itamar-website')->assertSuccessful();

    $counts = Entry::with('entryType')->get()->countBy(fn (Entry $e): string => $e->entryType->slug);

    expect($counts->all())->toEqual([
        'ig-biography' => 1,
        'news-articles' => 13,
        'ig-projects' => 13,
        'ig-landing-page-slideshow-images' => 10,
    ]);
});

it('writes nothing on a dry run', function (): void {
    $this->artisan('strapi:import-itamar-website', ['--dry-run' => true])->assertSuccessful();

    expect(Entry::count())->toBe(0);
});

it('serves the imported projects over the API key API', function (): void {
    $this->artisan('strapi:import-itamar-website')->assertSuccessful();

    $response = $this->getJson('/api/entries/by-type/ig-projects', ['X-API-Key' => $this->itamar->api_key])
        ->assertOk();

    $food = collect($response->json('data.entries'))->firstWhere('content.slug', 'food-chain-project');

    expect($food['title'])->toBe('Food Chain Project')
        ->and($food['order'])->toBe(8)
        ->and($food['content']['video_link'])->toBe('https://vimeo.com/877862246')
        ->and($food['content']['images'])->toHaveCount(20)
        ->and($food['content']['images'][0]['url'])->toStartWith('https://bengijzel.ams3.digitaloceanspaces.com/hiren-devs-strapi/')
        ->and($food['content']['images'][0]['path'])->toStartWith('hiren-devs-strapi/');
});
