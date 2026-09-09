# Coolify integration status (2026-09-09)

Branch: `coolify-integration`, cut from `origin/coolify` (not from `AI-REFACTOR`).
The point is one deploy-ready trunk for Coolify that keeps the two live client sites working.

## The strategy, and why it changed

Read `OTHER-MACHINE-BRIEFING-2026-09-09.txt` first. It reset my plan.

`origin/coolify` (`8acdb24`) is the real Coolify path. It's `origin/main` plus 11 commits: Laravel 13, PHP 8.5, local Postgres, a multi-stage Dockerfile (composer, then vite, then a php-fpm + nginx + supervisor runtime with the mlocati extension installer), Eloquent API resources, Form Requests, BaseEntity CRUD. So coolify is the base. We port only the client-critical bits from `AI-REFACTOR` on top. We do not merge main into `AI-REFACTOR`, and we do not rebuild main from `AI-REFACTOR`.

Fine to break during the move: Laravel 12 vs 13, the JSON import feature, theming and dark mode, the a11y pass, plan limits, the e2e suite, the 25 gd image tests, a tidy git history, force-pushing main. Not fine to break: the eight client-contract items in the briefing. Those are login for the two client users, the album, mosaic and entry lists, the published-entry API JSON shape (especially the Image Colors nested `json`), Spaces image URLs still returning 200, `/up`, and APP_URL, Sanctum and CORS matching the new host.

## What's on the branch now

I ported `AI-REFACTOR` commit `863ffd1`, the Image Colors entry types and the `json` field (it lands here as cherry-pick `190678f`). This is client-contract item 6, and coolify had neither the seeder nor the `json` type.

- `database/seeders/ImageColorsSeeder.php` seeds `parent-colors`, `preset` and `processed-image`, and grants them to `itamar@gilboa.net`. I checked it against coolify's Laravel 13 `User` model. Every field it touches (`entry_type_permissions`, `api_key`, `is_admin`, `is_approved`) is there.
- `app/Services/EntryValidationService.php` gets the `'json' => array` passthrough case, so a nested colour palette survives instead of being flattened to strings.
- `app/Http/Controllers/Admin/EntryTypeController.php` gets `json` added to the field-type whitelist, on both the store and update paths. Coolify had deleted `StoreEntryTypeRequest` and moved that validation inline into the controller, so I dropped the Form Request the cherry-pick tried to bring back.
- PHP lint passes on all three.

## What's left, in order

Nothing below is done yet.

1. Rebuild the container for Laravel 13 and PHP 8.5, then run the suite: `docker compose build && docker compose up -d && docker compose exec api composer install && docker compose exec api php artisan test`. The containers running right now are the old image, so don't trust a test run until you rebuild.
2. Seed the types (`php artisan db:seed --class=ImageColorsSeeder`), create a `processed-image` with a nested `json` palette, and read it back through `/api/entries/{id}` with Itamar's `X-API-Key`. Confirm the nested shape comes back intact.
3. Port the media-delete ownership check, the idea from `AI-REFACTOR` `3bedb8f`. Right now `app/Http/Requests/DeleteApiMediaRequest.php::authorize()` returns `true`, so any logged-in user can delete any Spaces path. Add a `userCanDeletePath` check. This is a security hole, not a client blocker.
4. Confirm the pagination 500 is gone. Coolify moved to Eloquent API resources and the old `BaseApiController` `->items()->map()` bug looks fixed, but hit `?page=` with a real key before you trust it.
5. Carry over whatever docs from `AI-REFACTOR` are still worth keeping (the PROJECT.md cockpit, roadmap, adversarial review, kickoff) if they aren't already here.
6. Run the eight-point smoke list from the briefing against a Coolify staging domain.
7. Only then make `coolify-integration` the trunk (force-push main) and do the Coolify infra: DNS and SSL, APP_URL plus Sanctum plus CORS, `DO_SPACES_*` set to the production bucket, the `/up` health check, and the Postgres data move by script. Keep DO up until both sites are confirmed green.

## Notes

A WIP edit to `docs/coolify-migration-kickoff.md` is stashed as `coolify-kickoff-wip`, left over from the `AI-REFACTOR` checkout. Pop it when you're back on that branch.

`img-clrs` is a separate job. It stays on Netlify, and the analysis renewal lives on branch `v2-analysis-renewal` (tests and build green, code review clean). It has nothing to do with this Coolify work.
