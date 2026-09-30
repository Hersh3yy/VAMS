<?php

declare(strict_types=1);

namespace App\Support\Ade;

use Carbon\CarbonImmutable;

/**
 * Derived facts about an ADE event, from ADE's own labels, times and title words.
 * ade:sync stores the result on each ade-event entry, so the admin shows it and
 * every client reads the same answer.
 */
final class AdeEventClassifier
{
    private const PARTY_TYPES = ['Nighttime events', 'All night long', 'Club nights'];

    /** ADE labels first, then title keywords for events ADE tagged loosely. */
    private const KIND_RULES = [
        'talks' => [['Keynotes, Talks & Panels', 'Indepth'], '/\b(talks?|panel|keynote|in conversation|q&a|interview)\b/i'],
        'masterclasses' => [['Masterclasses', 'Get into session'], '/master ?class|workshop/i'],
        'gear' => [['Brand demo', 'Gear'], '/\bgear\b|meet the makers|synth|\bdemos?\b|try.?out|hands.?on|modular/i'],
        'listening' => [[], '/listening|playback/i'],
        'showcases' => [["Showcases & Expo's"], '/showcase|\bexpo\b|market|\bfair\b/i'],
        'instore' => [['Instore Session'], '/record store|in-?store|signing|meet (&|and) greet/i'],
        'meet-the' => [['Meet the... Sessions'], null],
        'networking' => [['Networking events', 'Networking'], '/network|meet.?up|mixer|brunch|breakfast|drinks/i'],
        'art' => [['Exhibitions', 'Audiovisual & Immersive Arts'], '/exhibit|installation|gallery/i'],
        'film' => [['Film & Documentaries'], '/\bfilms?\b|documentar|screening/i'],
        'wellbeing' => [['Wellbeing', 'Sports'], '/yoga|breath|run club|meditat|sauna|sound bath/i'],
        'performances' => [['Live Performances'], null],
        'culture' => [['Music Culture', 'Lifestyle', 'Social impact', 'Dance & Theatre'], null],
    ];

    /**
     * A talk that is one person (or two) in conversation. Only applied to talks and ADE
     * Pro, so a party called "Chicago Meets Amsterdam" stays a party.
     */
    private const INTERVIEW_PATTERN = '/in conversation|a conversation (with|between)|\bmeets\b|\bq ?& ?a\b|interview|opens up|fireside|campfire/i';

    /** "... with Troy Carter": a talk named after its one guest. Case matters here. */
    private const WITH_GUEST_PATTERN = "/\\bwith [A-Z][\\w']+ [A-Z][\\w']+$/u";

    /** Kinds that still leave a music event a party (a concert, a lifestyle rave). */
    private const PARTY_COMPATIBLE_KINDS = ['culture', 'performances'];

    /** First matching intent wins, so a talk with drinks after is "learn". */
    private const INTENTS = [
        'learn' => ['talks', 'interviews', 'masterclasses', 'gear'],
        'meet' => ['meet-the', 'networking', 'showcases', 'instore'],
        'listen' => ['listening', 'film', 'art', 'performances'],
        'recharge' => ['wellbeing'],
    ];

    /** Longer than this, you drop in rather than arrive on time. */
    private const DROP_IN_MINUTES = 240;

    /**
     * @param  array{title: string, program?: ?string, eventTypes?: list<string>, tags?: list<string>, genres?: list<string>, startsAt?: ?string, endsAt?: ?string, ticketStatus?: ?string}  $event
     * @return array{kinds: list<string>, intent: string, timeOfDay: string, isParty: bool, access: string, format: string, durationMinutes: ?int}
     */
    public static function classify(array $event): array
    {
        $kinds = self::kinds($event);
        $timeOfDay = self::timeOfDay($event['startsAt'] ?? null, $event['endsAt'] ?? null);
        $isParty = self::isParty($event, $kinds, $timeOfDay);
        $duration = self::durationMinutes($event['startsAt'] ?? null, $event['endsAt'] ?? null);

        return [
            'kinds' => $kinds,
            'intent' => $isParty ? 'party' : self::intent($kinds),
            'timeOfDay' => $timeOfDay,
            'isParty' => $isParty,
            'access' => match (true) {
                self::isLabDiscovery($event) => 'free',
                ($event['program'] ?? null) === 'pro' => 'pro',
                ($event['ticketStatus'] ?? null) === 'free' => 'free',
                default => 'ticket',
            },
            'format' => match (true) {
                $timeOfDay === 'tba' => 'tba',
                $timeOfDay === 'all-day', $duration !== null && $duration >= self::DROP_IN_MINUTES => 'drop-in',
                default => 'session',
            },
            'durationMinutes' => $duration,
        ];
    }

    /** @return list<string> */
    public static function kinds(array $event): array
    {
        $labels = array_merge($event['eventTypes'] ?? [], $event['tags'] ?? []);
        $kinds = [];
        foreach (self::KIND_RULES as $kind => [$ruleLabels, $titlePattern]) {
            if (array_intersect($ruleLabels, $labels) !== [] || ($titlePattern && preg_match($titlePattern, $event['title']))) {
                $kinds[] = $kind;
            }
        }

        // Finer than ADE's labels: an interview instead of a talk, a "Meet the..."
        // session instead of general networking.
        $isTalk = in_array('talks', $kinds, true) || ($event['program'] ?? null) === 'pro';
        if ($isTalk && (preg_match(self::INTERVIEW_PATTERN, $event['title']) || preg_match(self::WITH_GUEST_PATTERN, $event['title']))) {
            $kinds = [...array_diff($kinds, ['talks']), 'interviews'];
        }
        if (in_array('meet-the', $kinds, true)) {
            $kinds = array_diff($kinds, ['networking']);
        }

        return array_values(array_unique($kinds));
    }

    /**
     * ADE Lab Discovery is free for everyone, even though ADE lists it with ADE Pro.
     *
     * @param  array{eventTypes?: list<string>, tags?: list<string>}  $event
     */
    public static function isLabDiscovery(array $event): bool
    {
        return in_array('Lab Discovery', [...($event['eventTypes'] ?? []), ...($event['tags'] ?? [])], true);
    }

    /**
     * Club-night types decide first. Otherwise an event is daytime only when it has a
     * clearly non-party kind or no music genre at all: a 16:00 rave or an evening
     * concert is still a party, ADE Pro never is.
     *
     * @param  list<string>  $kinds
     */
    public static function isParty(array $event, array $kinds, string $timeOfDay): bool
    {
        if (($event['program'] ?? null) === 'pro') {
            return false;
        }
        if (array_intersect(self::PARTY_TYPES, $event['eventTypes'] ?? []) !== []) {
            return true;
        }
        if (array_diff($kinds, self::PARTY_COMPATIBLE_KINDS) !== []) {
            return false;
        }

        return ($event['genres'] ?? []) !== [] || $timeOfDay === 'night';
    }

    /** @param  list<string>  $kinds */
    public static function intent(array $kinds): string
    {
        foreach (self::INTENTS as $intent => $members) {
            if (array_intersect($members, $kinds) !== []) {
                return $intent;
            }
        }

        return 'other';
    }

    public static function timeOfDay(?string $startsAt, ?string $endsAt): string
    {
        // ADE Pro lists unscheduled sessions with the same start and end.
        if ($startsAt && $endsAt && $startsAt === $endsAt) {
            return 'tba';
        }
        if (! $startsAt) {
            return 'tba';
        }

        $start = CarbonImmutable::parse($startsAt)->setTimezone('Europe/Amsterdam');
        $end = $endsAt ? CarbonImmutable::parse($endsAt)->setTimezone('Europe/Amsterdam') : null;

        // Installations and hubs run 00:00-23:59: all day, not a night event.
        if ($start->format('H:i') === '00:00' && (! $end || $end->format('H:i') === '23:59')) {
            return 'all-day';
        }

        return match (true) {
            $start->hour >= 6 && $start->hour < 12 => 'morning',
            $start->hour >= 12 && $start->hour < 17 => 'afternoon',
            $start->hour >= 17 && $start->hour < 21 => 'evening',
            default => 'night',
        };
    }

    public static function durationMinutes(?string $startsAt, ?string $endsAt): ?int
    {
        if (! $startsAt || ! $endsAt || $startsAt === $endsAt) {
            return null;
        }

        $minutes = (int) CarbonImmutable::parse($startsAt)->diffInMinutes(CarbonImmutable::parse($endsAt));

        // ADE sometimes lists an end before the start; that's no duration, not a negative one.
        return $minutes > 0 ? $minutes : null;
    }

    /** Same title at the same venue on several days is one exhibition, lab or hub. */
    public static function seriesKey(string $title, ?string $venue): string
    {
        $normalize = fn (string $text): string => trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)));

        return substr(sha1($normalize($title).'|'.$normalize($venue ?? '')), 0, 12);
    }
}
