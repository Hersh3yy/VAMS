<?php

declare(strict_types=1);

use App\Support\Ade\AdeEventClassifier;

$base = ['startsAt' => '2026-10-22T19:00:00+02:00', 'endsAt' => '2026-10-22T22:30:00+02:00', 'tags' => []];

it('keeps club nights, daytime raves and concerts as parties', function () use ($base): void {
    expect(AdeEventClassifier::classify([...$base, 'title' => 'Drumcode', 'eventTypes' => ['Nighttime events'], 'genres' => ['Techno']])['intent'])->toBe('party')
        ->and(AdeEventClassifier::classify([...$base, 'title' => 'Audio Obscura x EXHALE', 'eventTypes' => ['Daytime events'], 'genres' => ['Techno']])['isParty'])->toBeTrue()
        ->and(AdeEventClassifier::classify([...$base, 'title' => 'Paul Kalkbrenner LIVE', 'eventTypes' => ['Live Performances'], 'genres' => ['Techno']])['isParty'])->toBeTrue();
});

it('sends talks, meetups and exhibitions to daytime with an intent', function () use ($base): void {
    expect(AdeEventClassifier::classify([...$base, 'title' => 'VOYA Business & Networking Event', 'eventTypes' => ['Daytime events'], 'genres' => ['House']]))
        ->toMatchArray(['isParty' => false, 'intent' => 'meet'])
        ->and(AdeEventClassifier::classify([...$base, 'title' => 'Slam Bam! A Photography Exhibition', 'eventTypes' => ['Exhibitions'], 'genres' => []])['intent'])->toBe('listen')
        ->and(AdeEventClassifier::classify([...$base, 'title' => 'Meet The Labels & A&Rs (talk)', 'eventTypes' => [], 'genres' => []])['intent'])->toBe('learn');
});

it('marks long events as drop-ins and fixed slots as sessions', function () use ($base): void {
    expect(AdeEventClassifier::classify([...$base, 'title' => 'Tomorrowland Expo', 'eventTypes' => ["Showcases & Expo's"], 'startsAt' => '2026-10-22T10:00:00+02:00', 'endsAt' => '2026-10-22T18:00:00+02:00']))
        ->toMatchArray(['format' => 'drop-in', 'durationMinutes' => 480])
        ->and(AdeEventClassifier::classify([...$base, 'title' => 'Panel talk', 'eventTypes' => []])['format'])->toBe('session');
});

it('treats unscheduled ADE Pro sessions as time TBA and never a party', function (): void {
    $session = ['program' => 'pro', 'title' => 'Meet The AI Players', 'eventTypes' => [], 'tags' => [], 'genres' => ['Techno'],
        'startsAt' => '2026-10-22T12:00:00+02:00', 'endsAt' => '2026-10-22T12:00:00+02:00'];

    expect(AdeEventClassifier::classify($session))
        ->toMatchArray(['isParty' => false, 'timeOfDay' => 'tba', 'format' => 'tba', 'access' => 'pro', 'durationMinutes' => null]);
});

it('treats 00:00-23:59 installations as all day drop-ins', function (): void {
    expect(AdeEventClassifier::classify(['title' => 'Heliotrope installation', 'eventTypes' => ['Exhibitions'], 'tags' => [],
        'startsAt' => '2026-10-21T00:00:00+02:00', 'endsAt' => '2026-10-21T23:59:00+02:00']))
        ->toMatchArray(['timeOfDay' => 'all-day', 'format' => 'drop-in']);
});

it('groups the same event at the same venue into one series key', function (): void {
    expect(AdeEventClassifier::seriesKey('Het Ei by Touki Delphine', 'ADE Lab Village'))
        ->toBe(AdeEventClassifier::seriesKey('Het Ei  by Touki Delphine!', 'ADE Lab Village'))
        ->not->toBe(AdeEventClassifier::seriesKey('Het Ei by Touki Delphine', 'Paradiso'));
});
