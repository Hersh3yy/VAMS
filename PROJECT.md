<!--
  PROJECT.md — the cockpit for this repo. One file to open and know where things stand.
  Maintained by the `project-map` skill. The assessment (status/issues/roadmap) is
  refreshed each session; the Diary at the bottom only grows. App-code changes need a
  green light; main is merged to only when sure.
  Keep the section order and headings identical across every project.
-->
<!-- clickup_list:901508387774 -->

# VAMS — cockpit

**What it is** · Laravel headless CMS. Albums, mosaics, and user-defined Entry Types, read over an API-key API. It is the backend for the Shawn York portfolio, benjamingijzel.nl, and the image-colors app.
**Stack** · Laravel 13 · PHP 8.5 · PostgreSQL · Inertia + Vue 3 · Sanctum · Docker (multi-stage, nginx + php-fpm + supervisor)
**Status** · 🟢 Live on DigitalOcean from `main`: Laravel 13 on PHP 8.4 (pinned for the buildpack), API-key read and write API, ADE Planner data and commands (`ade:sync`, `ade:topics`, `ade:venues`, `ade:prices`, `ade:export`), 288 tests green at last run (3 skipped).
**Repo** · `github.com/Hersh3yy/VAMS` · single branch `main` (old branches deleted; `AI-REFACTOR` kept as tag `archive/AI-REFACTOR`)
**Hosting** · DigitalOcean App Platform app `sea-lion-app` at `https://app.use-vams.me` (CNAME to `sea-lion-app-h6f2o.ondigitalocean.app`), auto-deploys `main` with the Heroku PHP buildpack (`heroku-php-apache2 public/`, port 8080, TCP check), not the Dockerfile · Laravel Cloud is not used · Coolify on a VPS still planned
**ClickUp** · list `901508387774`
**Last assessed** · 2026-10-14 (re-assessment pass: clean tree, all pushed; caught the Guide up to the `ade:prices` / `ade-usage` work. Tests not re-run — Docker daemon down)

---

## Run it

```bash
# Docker. Compose service is `api`.
docker compose up -d
docker compose exec api php artisan migrate
docker compose exec api php artisan db:seed --class=ImageColorsSeeder   # image-colors entry types
docker compose exec api php artisan test
```
Watch out: the currently running `vams-api` container reads a `.env` that points `DB_HOST` at the production DigitalOcean database, not a local one. Artisan against it hits prod. Treat it as read-only unless you mean it.

## Status

One branch, `main`, auto-deployed to DigitalOcean. The coolify work is merged; Coolify itself is not deployed and the DB has not moved (e1-e6 still open). Three clients read VAMS: Shawn York's portfolio, benjamingijzel.nl, Image Colors v2 (which also writes). hiren.ninja's ADE Planner reads the ADE entry types and writes anonymous counters through `POST /api/ade-planner/stats`. ADE data on prod: about 1,350 events, 3,900+ artists, 348 venues (338 with coordinates), daytime events tagged with 16 topics. The sync runs by hand, no worker or schedule.

## Issues

Worst first.

| Sev | Issue | Where |
|---|---|---|
| serious | API keys stored and compared as plaintext, unindexed | `app/Http/Middleware/ValidateApiKey.php` |
| warning | Running container points at the production DO database (operational footgun) | local `.env` |
| verify | The old paginated-API 500 (`->items()->map()` on an array) looks fixed by the Eloquent-resource rewrite. Confirm with `?page=` before trusting it. | `app/Http/Controllers/Api` |

## Guide

- Read API is what the client sites use: `X-API-Key` header, then `GET /api/albums`, `/api/albums/by-title/{t}`, `/api/entries/by-type/{slug}`, `/api/entries/{id}`. Read-only by design. Writes go through the Inertia web routes with session and CSRF, not the API.
- Entry Types define a record's shape with a JSON `field_config`; `Entry.content` is a native Postgres JSON column. The `json` field type (ported this session) is a passthrough array, so a nested colour palette survives instead of being flattened to strings. Its whitelist lives in `EntryTypeController`, not a Form Request (coolify moved it there).
- Image Colors entry types are `parent-colors`, `preset`, `processed-image`, seeded by `ImageColorsSeeder` and granted to `itamar@gilboa.net`.
- Deploy is Dockerfile-based. The entrypoint runs `storage:link`, caches config and views, and runs `migrate --force` on boot (gated by `RUN_MIGRATIONS`). So a fresh deploy against an empty Postgres builds its own schema.
- ADE Planner commands (all run on the host against the prod DB, see Run it):
  - `php artisan ade:sync --reuse-pages` pulls the ADE program into `ade-event` and `ade-artist` (about 15-60 min, the artist pass is slow). It keeps the counters (hits, searches, favorites, opens, ticketClicks), lineups and topics.
  - `php artisan ade:topics --missing=storage/app/ade-topics-todo.jsonl` lists daytime events without topics; Claude classifies them into the 16 slugs in `AdeTopics::TOPICS`; `php artisan ade:topics <file>` imports.
  - `php artisan ade:venues` geocodes new venues into `ade-venue` (PDOK + Amsterdam districts); `--refresh` redoes all.
  - `php artisan ade:prices` reads the lowest ticket price from the shop ADE links to (Weeztix and Resident Advisor, public data, no key), for published events with an available ticket; stored on the `ade-event` as `priceFrom`/`priceSource`/`priceCheckedAt`, re-checked after 12 hours unless `--refresh`. `ade:sync` and `ade:export` keep the prices.
  - `php artisan ade:export` writes the snapshot hiren-ninja ships as its fallback (`server/assets/ade-planner/snapshot.json`).
  - `POST /api/ade-planner/stats` (API key, 600/min): anonymous counters (`hits`/`searches` on artists, `opens`/`ticketClicks`/`favorites` on events) plus the `ade-search` log and one per-day `ade-usage` entry of feature flags. The only ADE write.
- The DB move is documented: `docs/COOLIFY_DB_MIGRATION_GUIDE.md`. The strategy and the eight-point client smoke test are in `docs/coolify-integration-status.md` and `OTHER-MACHINE-BRIEFING-2026-09-09.txt`.

## Hard parts

### coolify is the trunk, not AI-REFACTOR

🔭 **What it does** — Three branches diverged from a December merge-base. `origin/main` and `origin/coolify` both moved to Laravel 13, PHP 8.5, and Docker on Hiren's other machine; `AI-REFACTOR` stayed on Laravel 12 and went its own way. The combine is not a merge. It bases on coolify and ports only the client-critical pieces from AI-REFACTOR (the Image Colors types, later the media-delete ownership idea), because everything else on AI-REFACTOR either exists on coolify already or is fine to drop.

⚖️ **Why this way** — Merging a Laravel 12 branch into a Laravel 13 branch fights the framework upgrade for no gain. The client contract is a handful of API shapes, not a git history.

🗣️ **Say it to a senior** — "coolify is the deploy branch. I rebased the strategy onto it and cherry-picked only what the clients actually need from the old refactor branch."

### The database move copies data, not schema

🔭 **What it does** — The migration guide does not `pg_dump` the DO schema. It lets `php artisan migrate` build the schema on the fresh Coolify Postgres, then copies data only (`pg_dump --data-only --disable-triggers`), excluding the ephemeral tables (sessions, cache, jobs, telescope). A sequence-reset block fixes the few bigint-id tables afterward.

⚖️ **Why this way** — Restoring a DO schema drags in roles, extensions, and ownership quirks and collides with Laravel's own `migrations` table. Letting Laravel own the schema guarantees the new database matches the code exactly.

🗣️ **Say it to a senior** — "Same-engine Postgres move. Laravel owns the schema on the new box, I copy data only, then reset the sequences. No dialect rewrite, no ownership drift."

### API-key writes without a permission layer

🔭 **What it does** — `ValidateApiKey` resolves `X-API-Key` to a `User` and installs it as `request->user()`. The new `storeWithApiKey`/`updateWithApiKey`/`destroyWithApiKey` then query through `$user->entries()->find($id)` — already filtered to that user — so another key's entry does not exist from this caller's point of view (404), and `hasEntryTypePermission()` gates which types the key may create. The same `EntryService` + `field_config` validation the Inertia UI uses performs the write.

⚖️ **Why this way** — A per-key scope/ACL system is the "proper" shape, but Hiren wanted no plan/rate/permission machinery. Owner-scoped queries plus the existing type grants give the same safety for one key = one account, with zero new concepts. 404 instead of 403 also avoids leaking which uuids exist.

🗣️ **Say it to a senior** — "API-key writes act as the key's user: ownership is enforced by querying through `$user->entries()`, type access by the existing grants — no new permission layer."

### Normalized Image Colors presets

🔭 **What it does** — Strapi held a preset as one row embedding every analysed image (hence its 413s). VAMS stores one `preset` entry plus one `processed-image` entry per image, linked by an `entry_relation` field — an array of entry uuids in `content.preset` (`['preset.*' => 'string|exists:entries,id']`). `strapi:import-image-colors` writes both from a bundled snapshot, idempotent by `content.strapi_id`; the app's gateway re-stitches them into the old embedded shape at the boundary.

⚖️ **Why this way** — Relations live in `content` json (no pivot table), which is what the field system already supports; a dedicated table would mean a parallel relation model for one consumer.

🗣️ **Say it to a senior** — "Presets are normalized into parent + child entries linked by an entry_relation uuid array; the client re-embeds them, so the 413 blob problem is gone without a new table."

### Venue geocoding with Dutch open data

🔭 **What it does** — `ade:venues` takes every distinct venue name and address from the ADE events and asks PDOK's Locatieserver (the national address register, free, no key) for a point. When the address says Amsterdam, the query carries `fq=gemeentenaam:Amsterdam`, so "Oosterdok 2" can't land in Utrecht. A town-level hit (no street) gets no coordinates, because a dot in the middle of a town is worse than none. The point then goes to Amsterdam's own geosearch for the stadsdeel, mapped to the planner's areas, plus an `acrossIj` flag for Noord (ferry time).

⚖️ **Why this way** — Google or Mapbox need keys and terms; PDOK is the government's authoritative register, exact for Dutch addresses. Filtering by municipality fixed the wrong-town matches without hand-fixing rows.

🗣️ **Say it to a senior** — "Venues are geocoded against PDOK with a municipality filter, town-only matches are dropped, and the district comes from Amsterdam's stadsdelen layer: 293 of 348 exact, 338 with coordinates."

### The Coolify VPS was cryptojacked, and why the redeploy has guardrails

🔭 **What it does** — On 2026-09-13 the Coolify VPS was found mining Monero and running bandwidth-selling proxyware for about seven weeks. The way in was an old, manually-run VAMS container that published php-fpm's FastCGI port 9000 straight to the internet; port 9000 lets anyone run code, no app bug needed. It never escaped the container, the host was clean, and the box was trimmed down to Coolify only. The one tainted thing left is the set of secrets that container's `.env` could read. See [[vams-vps-incident]] in memory.

⚖️ **Why this way** — So the Coolify redeploy has three non-negotiables. Publish no host ports and let Traefik reach the app on the internal network only (that is the whole fix for the entry vector). Rotate every secret that container held before go-live (`APP_KEY`, DO Spaces, DB password). And let Coolify generate the Postgres password via `SERVICE_PASSWORD_POSTGRES` rather than hand-copying it, because the first attempt returned a 500 on a password the Coolify UI showed but the database had never actually been set to.

🗣️ **Say it to a senior** — "The old box got popped through an exposed php-fpm port. The new deploy sits behind Traefik with zero published ports, rotated secrets, and a Coolify-generated DB password so the app and database can't drift."

## Roadmap — near future

- [ ] Deploy VAMS to Coolify from `main` using a repo `docker-compose` (app + Postgres), not the Coolify GUI resources. No published host ports (an exposed php-fpm:9000 is what got the box cryptojacked, see Hard parts); Coolify routes via Traefik with `SERVICE_FQDN_app_80`, and the Postgres password comes from `SERVICE_PASSWORD_POSTGRES` so app and DB can never drift (that drift was the 500 on the first attempt). Exposed port 80, health check `/up`, database drivers (no Redis). Compose shape drafted in the 2026-10-14 diary <!-- id:e1 cu:123kjkdmpnp -->
- [ ] Before go-live, rotate the credentials the cryptojacker could read: a fresh `APP_KEY` (do NOT reuse the leaked one), the DO Spaces key/secret, and the DO database password (see the incident memory) <!-- id:e2 cu:123kjkdmpnq -->
- [ ] Move the database per `docs/COOLIFY_DB_MIGRATION_GUIDE.md`: deploy once so `RUN_MIGRATIONS` builds the schema on the empty Postgres 18, then data-only dump from DO (db `main`, user `vams-user`), restore into the Coolify `vams` db, reset sequences, verify counts <!-- id:e3 cu:123kjkdmpnr -->
- [ ] Repoint every client env to the new host before deleting the DO app: Shawn's portfolio (off the raw `sea-lion...ondigitalocean.app` host), benjamingijzel.nl, Image Colors, hiren.ninja <!-- id:e4 cu:123kjkdmpnt -->
- [ ] Seed and smoke a nested `json` palette through `/api/entries/{id}` with Itamar's key on the Coolify host, and confirm the ADE reads work <!-- id:e5 cu:123kjkdmpnu -->
- [ ] Run the eight-point client smoke on the staging domain, then cut DNS; keep the DO app up as a rollback for a week <!-- id:e6 cu:123kjkdmpnv -->
- [x] Port the media-delete ownership check onto `DeleteApiMediaRequest::authorize()` <!-- id:e7 cu:123kjkdmpnw -->
- [x] ADE Planner data in the real (DigitalOcean) DB: `AdePlannerSeeder` + `ade:sync` ran there <!-- id:e8 cu:123kjkdmpnx -->
- [ ] Until ADE (21-25 Oct): run `php artisan ade:sync --reuse-pages` by hand now and then (new events, Pro times, sold out); then `php artisan ade:topics --missing=storage/app/ade-topics-todo.jsonl`, have Claude classify the new daytime events into the 16 topics, import with `php artisan ade:topics <file>`, `php artisan ade:venues` for new venues, `php artisan ade:prices` for new/updated ticket prices, and `ade:export` into hiren-ninja's snapshot <!-- id:e9 cu:123kjkdmpny -->
- [ ] benjamingijzel.nl ships its VAMS API key in the page (`vamsBgApiKey`); move the fetch behind a server route <!-- id:e10 cu:123kjkdmpnz -->

## Roadmap — far future

- [ ] Hash API keys (store sha256, unique index, look up by hash) <!-- id:f1 cu:123kjkdmpp0 -->
- [ ] Carry over the useful docs from `AI-REFACTOR` (adversarial review, roadmap, cockpit history) if wanted <!-- id:f2 cu:123kjkdmpp1 -->
- [x] Write API for entries plus token auth, which unblocks image-colors persistence into VAMS — done 2026-10-01 on branch `image-colors-v2`: `POST/PUT/PATCH/DELETE /api/entries[/{id}]` under `ValidateApiKey`, owner-scoped, 5 feature tests. Merge to `main` = deploy (DO auto-deploys `main`) <!-- id:f3 cu:123kjkdmpp2 -->
- [ ] Account tiers (deferred: a product decision, not a build) <!-- id:f4 cu:123kjkdmpp3 -->
- [ ] Decide Redis or database for session/cache/queue once real traffic is known <!-- id:f5 cu:123kjkdmpp4 -->

---

## Diary

<!-- Newest first. One entry per working session. Terse, factual, honest. Append only. -->

### 2026-10-14 — re-assessment pass, Guide catch-up on ADE pricing
- Assessment only, no app-code changes. `main` is clean and 0 ahead / 0 behind `origin/main`: everything is pushed. Docker daemon is down, so the test suite was not re-run (still 288 green at the last run).
- Caught the map up to the ADE work that landed since the 10-11 detailed update but was only mentioned in passing by the 10-14 recap: `ade:prices` (new command, commit `c58156f`; lowest ticket price from Weeztix + Resident Advisor onto `ade-event`, re-checked after 12h; `ade:export` now includes it, `068e508`), the per-day `ade-usage` feature-flag counter on the stats endpoint (`36a8684`), and two `ade:sync` resilience fixes (ride out slow pages / drops `0ab7534`, raise memory to 1G `e0011f3`). Added `ade:prices` to Status, the Guide command list, and the ADE sync routine (e9); documented the fuller stats-endpoint shape in the Guide.
- Nothing new is red that wasn't already: Coolify deploy (e1-e6) and the credential rotation (e2) remain the open front; the DB move has not run.

### 2026-10-14 — two-month recap and Coolify deploy handoff
- Assessment only, no app-code changes. `main` is 0 ahead of origin (everything already pushed), single branch, clean hygiene. Pushed the refreshed cockpit so the other laptop picks up current.
- The last two months in one line: merged the coolify-integration work into `main` and deleted every old branch (27 Sep), fixed the by-title 500 and pinned PHP 8.4 for the DO buildpack, built the whole **ADE Planner** data layer for hiren.ninja (`ade:sync`/`ade:topics`/`ade:venues`/`ade:export`/`ade:prices`, event classifier, PDOK venue geocoding, anonymous stats API, about 1,600 events, 4,500 artists, 348 venues on prod), hosted Itamar Gilboa's website content, and shipped the API-key **write** API plus migrated Image Colors presets out of Strapi (f3 done, live on prod 6 Oct).
- Reframed the stale Coolify roadmap (e1-e6) to the real plan: deploy from `main` via a repo docker-compose (app + Postgres), no published ports, Traefik via `SERVICE_FQDN_app_80`, DB password via `SERVICE_PASSWORD_POSTGRES`, port 80, health `/up`, database drivers (no Redis). Added a Hard-parts block on the September cryptojacking so the next laptop deploys with the guardrails. The compose shape for that deploy:

```yaml
services:
  app:
    build: { context: ., target: app }
    environment:
      - SERVICE_FQDN_APP_80            # Coolify makes the domain + Traefik route to app:80
      - APP_NAME=VAMS
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_KEY=${APP_KEY}             # set once in Coolify env (rotated secret)
      - APP_URL=${SERVICE_URL_APP_80}
      - RUN_MIGRATIONS=true
      - DB_CONNECTION=pgsql
      - DB_HOST=postgres
      - DB_PORT=5432
      - DB_DATABASE=vams
      - DB_USERNAME=vams
      - DB_PASSWORD=${SERVICE_PASSWORD_POSTGRES}   # generated + synced by Coolify
      - SESSION_DRIVER=database
      - SESSION_SECURE_COOKIE=false    # http sslip.io; true on a real https domain
      - SANCTUM_STATEFUL_DOMAINS=${SERVICE_FQDN_APP_80}
      - CACHE_STORE=database
      - QUEUE_CONNECTION=database
      - FILESYSTEM_DISK=local
      - MAIL_MAILER=log
      - DO_SPACES_KEY=${DO_SPACES_KEY}
      - DO_SPACES_SECRET=${DO_SPACES_SECRET}
      - DO_SPACES_REGION=${DO_SPACES_REGION:-ams3}
      - DO_SPACES_BUCKET=${DO_SPACES_BUCKET:-bengijzel}
      - DO_SPACES_ENDPOINT=${DO_SPACES_ENDPOINT:-https://ams3.digitaloceanspaces.com}
    depends_on:
      postgres: { condition: service_healthy }
  postgres:
    image: postgres:18-alpine
    environment:
      - POSTGRES_USER=vams
      - POSTGRES_PASSWORD=${SERVICE_PASSWORD_POSTGRES}
      - POSTGRES_DB=vams
    volumes: [ "pg-data:/var/lib/postgresql/data" ]
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U vams -d vams"]
      interval: 5s
      timeout: 5s
      retries: 10
volumes:
  pg-data:
```
  Secrets that still live in the Coolify env UI (not the repo): `APP_KEY`, `DO_SPACES_KEY`, `DO_SPACES_SECRET`. A compose Postgres is not a managed database, so backups become a scheduled `pg_dump`, not a GUI button.
- Still red before Coolify go-live: rotate the leaked creds (`APP_KEY`, DO Spaces, DB password); the DB move (e3) has not run.
- Housekeeping: the untracked 606 KB SQLite file `vams` (a stray local DB) is now gitignored, not committed. Also produced an explain-like-im-nami deck of the project.

### 2026-10-11 — venues, topics, resync with diff
- `ade:venues` (new): geocodes each distinct venue once into `ade-venue` (PDOK Locatieserver with `fq=gemeentenaam:Amsterdam` when the address says Amsterdam, district from Amsterdam's geosearch stadsdelen, `acrossIj` for Noord; town-level hits get no coordinates). 348 venues, 293 exact, 46 street-level, 338 with coordinates. Commit `6dee296`.
- `ade:topics` (new): imports Claude's per-event topic classification (16 slugs in `AdeTopics::TOPICS`), `--missing=` lists what's left. `ade:sync` keeps `topics`; `ade:export` now includes topics and venues. Commit `97d8de8`.
- Stats endpoint takes `opens`/`ticketClicks` per event and the `ade-search` log (commit `afbbd68`); Tidal/Deezer as search sources (`b98f20e`). 288 tests green.
- Resync `ade:sync --reuse-pages` took about 45 min (394 new artist pages). Diff against the pre-sync export: events 1,405 -> 1,602 (+206: 121 daytime, 72 ADE Pro; -9 unpublished, incl. Amsterdam Rave, Luciano ALIVE, LEHAR & Friends x Crane Sessions), artists 4,154 -> 4,504 (+398, 48 unpublished), sold out 36 -> 45 (DGTL Thursday and Friday, Warhaus, Christian Löffler & Adna). 13 start and 21 end times moved, 38 titles, 99 lineups, 2 venues (De Wester -> WestWeelde). Lineups on 1,112 of 1,602 events.
- 106 daytime events classified and imported (0 left). `ade:venues`: nothing new to look up. Snapshot exported to hiren-ninja.

### 2026-10-06 — write API confirmed live; branch retired
- `main` (with `6a5ca5d` migration, `22d0fb6` API-key write API, `252fd2b` docs) was pushed by a later sitting and auto-deployed: `POST/PUT/DELETE https://app.use-vams.me/api/entries` answer 401 without a key (route live, middleware gating). Image Colors v2 can now create/update/delete presets against prod.
- Deleted local branch `image-colors-v2` (superseded by `main`). Single branch again: `main`.
- Still open from this arc: `parent-colors` type empty (app task V6b in img-clrs); API keys still plaintext (f1).

### 2026-10-01 — Image Colors v2: write API + preset migration (branch `image-colors-v2`)
- Hiren's prio: the image-colors app must **create new presets** in VAMS. API-key routes were read-only by design, so added `storeWithApiKey`/`updateWithApiKey`/`destroyWithApiKey` to `Api\EntryController` + routes; reuses `EntryService` + `EntryValidationService` + `hasEntryTypePermission`; writes scoped to the key owner via `$user->entries()`; deliberately no plan/rate gate. `tests/Api/EntryWriteApiTest.php` 5 green. Commit `22d0fb6`.
- `strapi:import-image-colors` (mirrors `ImportItamarWebsite`): bundled snapshot `database/data/image-colors-strapi-snapshot.json`, idempotent by `content.strapi_id`. **Ran on prod**: 22 `preset` + 173 `processed-image` entries for itamar@gilboa.net. Commit `6a5ca5d`. `parent-colors` still empty.
- Ran `ImageColorsSeeder` on prod (idempotent; types already existed) — itamar's api_key unchanged.
- Gotchas: the local `api` container is php 8.3 while composer now needs 8.4.* → `docker compose exec api artisan` fatals; ran artisan on the host (php 8.5), which — per the Run-it warning — hits the **prod** DB. Branch code was served on host `:8787` to verify write routes before any deploy. Not merged, not pushed.

### 2026-09-30 — ADE classifier: interviews, Meet the..., demos; daily sync
- `AdeEventClassifier`: new kinds `interviews` (split off talks for talks and ADE Pro only, so "Chicago Meets Amsterdam" stays a party), `meet-the` (ADE's "Meet the... Sessions", split off networking), `performances` (Live Performances, still party-compatible). `gear` now also from ADE's "Brand demo"/"Gear" labels and "Meet the Makers". ADE Lab Discovery counts as free. 11 classifier tests.
- ADE published the Pro timetable on 30 Sep; resynced with `--reuse-pages` (14 min: 1,349 events, 3,946 artists, 972 events with a lineup). No schedule: run `php artisan ade:sync --reuse-pages` by hand when ADE updates (about 8-15 min).
- `POST /api/ade-planner/stats` (API key, own 600/min limit): anonymous counters `hits` on ade-artist and `favorites` on ade-event, row-locked, never below zero, only the key owner's ADE entries. `ade:sync` preserves both. Seeder adds the two fields. The API's one write.
- New project entry "ADE Planner" for hiren.ninja (order 0), all project orders made unique.

### 2026-09-28 — entry field system polish, ADE relations and tickets
- One shared `usesFieldForm()` rule: types with real fields now use the field form and read view everywhere (was only for repeatable/image/object/relation). New field types `datetime` and `url`, per-field `summary` flag for list cards, relation picker and clickable relation chips, `GET /entries/lookup` and `/entries/search`. 259 tests.
- ADE: events and artists linked both ways (lineup / events), ticket status from ADE's ticketsAvailable filter, ticket link and address from event pages, categories split into genres / event types / area / tags.
- Fixed by-title 500 for albums and mosaics (benjamingijzel.nl fallback images), pinned PHP 8.4 + restored `.user.ini` for the DigitalOcean buildpack.
- Open: this Mac's IP can't reach the DigitalOcean DB anymore (trusted sources?), so `db:seed --class=AdePlannerSeeder` + `ade:sync --only=events` must run in the App Platform console.


### 2026-09-27 — main merge, return types, ADE Planner commands
- Added `ade-artist` / `ade-event` entry types (`AdePlannerSeeder`) and `ade:sync` / `ade:export` for hiren.ninja/ade-planner. Ran them from the local container, so the data sits in the DigitalOcean DB: 1,104 events plus 3,356 artists.
- The local container was an old image (PHP 8.3, Laravel 12 in vendor); my host `composer install` then broke it (platform check), rebuild it.
- Return types on 423 functions, arrow functions and closures (79 files). Ported the media-delete ownership check and the test bootstrap from AI-REFACTOR; the bootstrap now also forces in-memory SQLite so tests can never touch prod. Composer update to Laravel 13.33 (Guzzle 8). 241 tests pass on PHP 8.5.
- Compared live API shapes (albums, mosaics, entries) with the branch: identical. Fast-forwarded `main`, deleted coolify, coolify-integration, dev, post-mvp-cleanup, EntriES, entry-type-updates and AI-REFACTOR (tagged `archive/AI-REFACTOR`).
- The merge deploy (3ac536b) failed the readiness probe: the Heroku buildpack took `^8.5` as PHP 8.5, and `.user.ini` had been deleted and gitignored. VAMS was already unreachable before that (DO hostname NXDOMAIN). Pinned `8.4.*`, restored `.user.ini` (fc2c6f3): deploy healthy, `app.use-vams.me` serves again.
- Left: rebuild the local container from the Dockerfile; decide if Coolify still replaces DigitalOcean.

### 2026-09-10 — combine onto coolify, first cockpit on this branch
- Used the other-machine briefing to remap the branches. `origin/coolify` is the real deploy branch (L13, PHP 8.5, Docker, Eloquent resources); `AI-REFACTOR` diverged and is no longer trunk.
- Cut `coolify-integration` off `origin/coolify`. Ported the Image Colors entry types + `json` field (cherry-pick `190678f`). Resolved the conflict by adding `json` to the `EntryTypeController` whitelist, since coolify had deleted `StoreEntryTypeRequest`. PHP lint clean. Seeder verified against coolify's L13 `User`.
- Wrote `docs/coolify-integration-status.md` (the ordered remainder) and this cockpit. Confirmed the repo already ships `docs/COOLIFY_DB_MIGRATION_GUIDE.md` for the data move.
- Left red: not deployed to Coolify, DB not moved, `DeleteApiMediaRequest::authorize()` still returns `true`, test suite not yet run on L13/PHP 8.5. Nothing pushed; `main` untouched.

### 2026-09-29 — Itamar's website content (branch `itamar-website`)
- The DigitalOcean Strapi behind itamargilboa.com went down (it answered again when checked). Snapshotted its four collections into `database/data/itamargilboa-strapi-snapshot.json` so the move no longer depends on Strapi staying up.
- `ItamarWebsiteSeeder` adds entry types `ig-biography`, `news-articles`, `ig-projects`, `ig-landing-page-slideshow-images` (same slugs as Strapi) and grants them to `itamar@gilboa.net`. If his account is missing it is created with a random password, never a default one.
- `strapi:import-itamar-website` (with `--dry-run`) writes 37 entries, matched by `content.strapi_id` so re-runs update in place. Images keep their `bengijzel` Spaces URLs; `path` is the in-bucket path, so VAMS media handling still works on them.
- 3 new tests; suite 262 passed, 3 skipped. Also ran seeder + import twice on a scratch local Postgres (37 entries, no duplicates) and served it to the site locally.
- Not yet on production: the prod DB refused connections from this machine (DO trusted sources). Run on prod: `php artisan db:seed --class=ItamarWebsiteSeeder --force` then `php artisan strapi:import-itamar-website`.
- Side note: the `vams-api` container runs PHP 8.3 and can no longer boot the current vendor (needs >= 8.4.1). Host PHP 8.5 works.
