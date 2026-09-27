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
**Status** · 🟡 `main` now carries the coolify work (fast-forwarded 2026-09-27): Laravel 13.33, PHP 8.5, return types everywhere, media-delete fix, 241 tests green. Two databases exist: the live app on Laravel Cloud has its own, and the local container still points at the DigitalOcean one that no running app serves.
**Repo** · `github.com/Hersh3yy/VAMS` · single branch `main` (old branches deleted; `AI-REFACTOR` kept as tag `archive/AI-REFACTOR`)
**Hosting** · live on Laravel Cloud (`vams-main-qvek1c.laravel.cloud`, needs PHP 8.5 set there) · DigitalOcean app and `app.use-vams.me` are gone · Coolify on a VPS still planned
**ClickUp** · list `901508387774`
**Last assessed** · 2026-09-27

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

`coolify-integration` was cut from `origin/coolify`, the real deploy branch. That branch is `origin/main` plus 11 commits: Laravel 13, PHP 8.5, local Postgres, a working multi-stage Dockerfile, and Eloquent API resources. On top of it I ported the Image Colors entry types and the `json` field type from `AI-REFACTOR`, which coolify was missing. Nothing is deployed to Coolify yet, the DB has not moved, and two security items are still open. `main` is the older DigitalOcean version and has diverged from coolify.

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

## Roadmap — near future

- [ ] Deploy `coolify-integration` to Coolify: Postgres 17, the env block (`APP_KEY`, `APP_URL`, `RUN_MIGRATIONS=true`, `DO_SPACES_*` on the prod bucket, session/CORS/Sanctum on the new host), `/up` health check, domain <!-- id:e1 -->
- [ ] Rebuild for L13/PHP 8.5 and run the suite; confirm the gd image tests pass now (mlocati installer keeps the runtime libs) <!-- id:e2 -->
- [ ] Move the database per `docs/COOLIFY_DB_MIGRATION_GUIDE.md`: deploy once to build the schema, then data-only dump from DO, restore, reset sequences, verify counts <!-- id:e3 -->
- [ ] Repoint Shawn's portfolio env from the raw `sea-lion...ondigitalocean.app` host to `app.use-vams.me` before the DO app is deleted <!-- id:e4 -->
- [ ] Seed and smoke a nested `json` palette through `/api/entries/{id}` with Itamar's key <!-- id:e5 -->
- [ ] Run the eight-point client smoke on a staging domain, then cut over DNS <!-- id:e6 -->
- [x] Port the media-delete ownership check onto `DeleteApiMediaRequest::authorize()` <!-- id:e7 -->
- [ ] Decide which database is the real one (Laravel Cloud vs DigitalOcean), point the local `.env` at it, then run `db:seed --class=AdePlannerSeeder` and `ade:sync` there <!-- id:e8 -->

## Roadmap — far future

- [ ] Hash API keys (store sha256, unique index, look up by hash) <!-- id:f1 -->
- [ ] Carry over the useful docs from `AI-REFACTOR` (adversarial review, roadmap, cockpit history) if wanted <!-- id:f2 -->
- [ ] Write API for entries plus token auth, which unblocks image-colors persistence into VAMS <!-- id:f3 -->
- [ ] Account tiers (deferred: a product decision, not a build) <!-- id:f4 -->
- [ ] Decide Redis or database for session/cache/queue once real traffic is known <!-- id:f5 -->

---

## Diary

<!-- Newest first. One entry per working session. Terse, factual, honest. Append only. -->

### 2026-09-27 — main merge, return types, ADE Planner commands
- Added `ade-artist` / `ade-event` entry types (`AdePlannerSeeder`) and `ade:sync` / `ade:export` for hiren.ninja/ade-planner. Ran them from the local container, so the data sits in the DigitalOcean DB: 1,104 events plus 3,356 artists.
- Found the live VAMS is Laravel Cloud with a separate DB; `app.use-vams.me` no longer resolves. The local container was also an old image (PHP 8.3, Laravel 12 in vendor).
- Return types on 423 functions, arrow functions and closures (79 files). Ported the media-delete ownership check and the test bootstrap from AI-REFACTOR; the bootstrap now also forces in-memory SQLite so tests can never touch prod. Composer update to Laravel 13.33 (Guzzle 8). 241 tests pass on PHP 8.5.
- Compared live API shapes (albums, mosaics, entries) with the branch: identical. Fast-forwarded `main`, deleted coolify, coolify-integration, dev, post-mvp-cleanup, EntriES, entry-type-updates and AI-REFACTOR (tagged `archive/AI-REFACTOR`).
- Left: set PHP 8.5 on Laravel Cloud if the deploy fails; pick the real DB; rebuild the local container from the Dockerfile.

### 2026-09-10 — combine onto coolify, first cockpit on this branch
- Used the other-machine briefing to remap the branches. `origin/coolify` is the real deploy branch (L13, PHP 8.5, Docker, Eloquent resources); `AI-REFACTOR` diverged and is no longer trunk.
- Cut `coolify-integration` off `origin/coolify`. Ported the Image Colors entry types + `json` field (cherry-pick `190678f`). Resolved the conflict by adding `json` to the `EntryTypeController` whitelist, since coolify had deleted `StoreEntryTypeRequest`. PHP lint clean. Seeder verified against coolify's L13 `User`.
- Wrote `docs/coolify-integration-status.md` (the ordered remainder) and this cockpit. Confirmed the repo already ships `docs/COOLIFY_DB_MIGRATION_GUIDE.md` for the data move.
- Left red: not deployed to Coolify, DB not moved, `DeleteApiMediaRequest::authorize()` still returns `true`, test suite not yet run on L13/PHP 8.5. Nothing pushed; `main` untouched.
