<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Database\Seeders\AdePlannerSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Ticket prices for ADE events, read from the ticket shop ADE links to. ADE itself
 * publishes no prices, so this is a separate step after ade:sync:
 *
 *   php artisan ade:prices            check events not checked in the last 12 hours
 *   php artisan ade:prices --refresh  check every event with a ticket link again
 *
 * Supported shops (public data, no key): Weeztix (shop.api.openticket.tech, the
 * lowest available ticket incl. service costs) and Resident Advisor (its GraphQL,
 * the lowest ticket on sale). Other shops block bots or render prices client-side,
 * so their events keep no price. Stored on the ade-event as `priceFrom` (euros),
 * `priceSource` and `priceCheckedAt`; ade:sync keeps them.
 */
class AdePrices extends Command
{
    private const RECHECK_AFTER_HOURS = 12;

    protected $signature = 'ade:prices {--refresh : Check every event again}';

    protected $description = 'Read the lowest ticket price of ADE events from Weeztix and Resident Advisor';

    public function handle(): int
    {
        $owner = User::where('email', AdePlannerSeeder::OWNER_EMAIL)->firstOrFail();
        $type = EntryType::where('slug', 'ade-event')->firstOrFail();
        $events = Entry::where('entry_type_id', $type->id)->where('user_id', $owner->id)->where('status', 'published')->get()
            ->filter(fn (Entry $event): bool => ($event->content['ticketStatus'] ?? null) === 'available'
                && self::shopOf((string) ($event->content['ticketUrl'] ?? '')) !== null)
            ->reject(fn (Entry $event): bool => ! $this->option('refresh') && isset($event->content['priceCheckedAt'])
                && Carbon::parse($event->content['priceCheckedAt'])->gt(now()->subHours(self::RECHECK_AFTER_HOURS)));
        $this->info("Events with a supported ticket shop to check: {$events->count()}");

        $bar = $this->output->createProgressBar($events->count());
        $found = 0;
        foreach ($events as $event) {
            [$shop, $id] = self::shopOf($this->resolve((string) $event->content['ticketUrl'])) ?? [null, null];
            $price = match ($shop) {
                'weeztix' => $this->weeztix($id, (string) ($event->content['startsAt'] ?? '')),
                'ra' => $this->residentAdvisor($id),
                default => null,
            };
            $content = [...$event->content, 'priceCheckedAt' => now()->toIso8601String()];
            if ($price !== null) {
                $found++;
                $content['priceFrom'] = $price;
                $content['priceSource'] = $shop;
            }
            Entry::withoutEvents(fn () => $event->update(['content' => $content]));
            $bar->advance();
            usleep(250_000);
        }
        $bar->finish();
        $this->newLine();
        $this->info("Prices found: {$found} of {$events->count()}.");

        return self::SUCCESS;
    }

    /**
     * Which shop a ticket link points to, and that shop's id for the event.
     * Outlook "safelinks" wrappers and weeztix.shop short links count too.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function shopOf(string $url): ?array
    {
        if (preg_match('~safelinks\.protection\.outlook\.com/.*[?&]url=([^&]+)~', $url, $wrapped)) {
            $url = urldecode($wrapped[1]);
        }

        return match (true) {
            (bool) preg_match('~shop\.weeztix\.com/([0-9a-f-]{36})~i', $url, $match) => ['weeztix', strtolower($match[1])],
            (bool) preg_match('~^https?://weeztix\.shop/[a-z0-9]+~i', $url, $match) => ['weeztix-short', $match[0]],
            (bool) preg_match('~ra\.co/events/(\d+)~', $url, $match) => ['ra', $match[1]],
            default => null,
        };
    }

    /** Follow a weeztix.shop short link to the shop it redirects to. */
    private function resolve(string $url): string
    {
        [$shop, $short] = self::shopOf($url) ?? [null, null];
        if ($shop !== 'weeztix-short') {
            return $url;
        }

        return (string) ($this->http()->withoutRedirecting()->get($short)->header('Location') ?: $url);
    }

    /** Lowest ticket you can buy now, in euros (Weeztix gives cents, incl. service costs). */
    private function weeztix(string $shopId, string $startsAt): ?float
    {
        $data = $this->http()->get("https://shop.api.openticket.tech/{$shopId}/data")->json();
        if (! is_array($data) || empty($data['tickets'])) {
            return null;
        }
        // A shop can sell several nights; keep the tickets of the one on this event's date.
        $day = $startsAt !== '' ? Carbon::parse($startsAt)->setTimezone('Europe/Amsterdam')->toDateString() : null;
        $sameDay = collect($data['events'] ?? [])
            ->filter(fn (array $event): bool => $day && isset($event['start']) && Carbon::parse($event['start'])->setTimezone('Europe/Amsterdam')->toDateString() === $day)
            ->pluck('guid');

        $prices = collect($data['tickets'])
            ->filter(fn (array $ticket): bool => ($ticket['status'] ?? 'available') === 'available'
                && empty($ticket['deleted_at']) && empty($ticket['hide_without_coupon']) && empty($ticket['require_coupon'])
                && ($sameDay->isEmpty() || $sameDay->contains($ticket['event_id'] ?? null)))
            ->pluck('min_price')
            ->filter(fn (mixed $price): bool => is_numeric($price));

        return $prices->isEmpty() ? null : round($prices->min() / 100, 2);
    }

    /** Lowest ticket on sale on Resident Advisor, or the event's listed cost ("15€", "Free"). */
    private function residentAdvisor(string $eventId): ?float
    {
        $event = $this->http()
            ->withHeaders(['Referer' => "https://ra.co/events/{$eventId}"])
            ->post('https://ra.co/graphql', [
                'query' => 'query($id: ID!) { event(id: $id) { cost tickets(queryType: AVAILABLE) { priceRetail validType isAddOn } } }',
                'variables' => ['id' => $eventId],
            ])->json('data.event');
        if (! is_array($event)) {
            return null;
        }

        $tickets = collect($event['tickets'] ?? [])->filter(fn (array $ticket): bool => empty($ticket['isAddOn']));
        $onSale = $tickets->where('validType', 'VALID')->pluck('priceRetail')->filter(fn (mixed $price): bool => is_numeric($price));
        if ($onSale->isNotEmpty()) {
            return round((float) $onSale->min(), 2);
        }
        if ($tickets->isNotEmpty()) {
            return null; // all sold out or not on sale yet
        }

        return self::parseCost((string) ($event['cost'] ?? ''));
    }

    /** "15€", "€12,50 - €20", "Free", "Free before 23:00" -> lowest amount in euros. */
    public static function parseCost(string $cost): ?float
    {
        if (preg_match('/^\s*(free|gratis)\b/i', $cost)) {
            return 0.0;
        }
        preg_match_all('/(\d+(?:[.,]\d{1,2})?)/', $cost, $numbers);
        $amounts = array_map(fn (string $n): float => (float) str_replace(',', '.', $n), $numbers[1]);

        return $amounts ? min($amounts) : null;
    }

    private function http(): PendingRequest
    {
        // RA's GraphQL answers a browser-like client; Weeztix doesn't mind.
        return Http::withUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128 Safari/537.36')
            ->acceptJson()->timeout(15)->retry(2, 500, throw: false);
    }
}
