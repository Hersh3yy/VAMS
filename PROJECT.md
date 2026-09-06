<!--
  PROJECT.md — the cockpit for this repo. One file to open and know where things stand.
  Maintained by the `project-cockpit` skill. The assessment (status/issues/roadmap) is
  refreshed each session; the Diary at the bottom only grows. App-code changes need a
  green light; main is merged to only when sure.
-->
<!-- clickup_list:901508387774 -->

# VAMS — cockpit

**What it is** · Laravel headless CMS (Visual Album Management System): albums, mosaics, and user-defined Entry Types, read over an API-key API. Backend-to-be for the Image Colors app.
**Stack** · Laravel 12 · PHP 8.3 · PostgreSQL 15 · Inertia + Vue 3 · Sanctum · Docker
**Status** · 🟡 rough — works and is deployed, but the test suite only just came back to life (168 pass / 25 fail) and the security backlog is open. Not production-ready.
**Repo** · GitLab (Itamar) · working on `AI-REFACTOR` (ahead of and healthier than `main`; merge to `main` only when sure)
**Hosting** · DigitalOcean today · **migrating to Coolify** (in flight)
**ClickUp** · not linked yet (set list id in the comment above + rotate the leaked key)
**Last assessed** · 2026-09-06

---

## Run it

```bash
# Docker. The compose service is `api` (not `app`). docker-compose.yml is gitignored/local.
docker compose up -d
docker compose exec api php artisan migrate
docker compose exec api php artisan db:seed --class=ImageColorsSeeder   # image-colors entry types
docker compose exec api php artisan test                                # 168 pass / 25 fail / 3 skipped
```
Gotcha: the `gd` PHP extension is missing in the image — every artisan call warns, and the 25 failing tests are all image-upload `LogicException`s from it. Local Postgres db is `vams_local`; runtime env is `local` (tests force `testing` via `tests/bootstrap.php`).

## Status

The suite went from 76 → 168 passing this session after fixing an env-shadowing bug (the container's `APP_ENV=local` outranked phpunit's `testing`, so CSRF never skipped in tests → 419 on every write test). The three Image Colors entry types are seeded and granted to `itamar@gilboa.net`. The core API-key read flow works. What's not done: the security hardening, the `gd`-dependent image tests, and the Coolify move.

## Issues

Full detail with file:line in `docs/adversarial-review.md`.

| Sev | Issue | Where |
|---|---|---|
| critical | Entry media upload always 403s — gate calls `Gate::allows('update',$entry)` but no `EntryPolicy` is registered | `app/Http/Controllers/MediaUploadController.php:246` |
| serious | Paginated API 500 — `->items()->map()` on a plain array | `app/Http/Controllers/Api/BaseApiController.php:49` |
| serious | API keys stored plaintext, unindexed, compared with a plain `where` | `app/Http/Middleware/ValidateApiKey.php:28` |
| serious | CORS `'*'` combined with `supports_credentials` | `config/cors.php` |
| warning | `user_id` in `Entry::$fillable` (mass-assignment footgun) | `app/Models/Entry.php:15` |
| warning | 25 tests fail — missing `gd` extension in the container image | `app/Services/ImageService.php` |

## Guide

- **Read API** (what the Image Colors app uses): `routes/api.php` → `ValidateApiKey` (X-API-Key) → `Api/EntryController` → `EntryService::getEntriesForApi` (user-scoped, published-only, permission-filtered). Read-only by design; no write API yet.
- **Write path** is Inertia web routes (session + CSRF), not the API.
- **Entry Types** define a record shape via a JSON `field_config`; `Entry.content` is a native Postgres JSON column. Field types incl. the new `json` passthrough (added this session for the colour payloads). Validation: `app/Services/EntryValidationService.php` (shallow — only one level of nesting).
- **This project's entity types** (`ImageColorsSeeder`): `parent-colors`, `preset`, `processed-image` — `processed-image` relates up to its `preset` and to the `parent-colors` version used.
- **Base classes** `BaseEntityService`/`BaseEntityController` are a template the Entry path mostly bypasses (a Refused-Bequest smell — decide to use or collapse).
- Planning docs live in `docs/`: roadmap (`vams-renewal-plan.html`), adversarial review, kickoff prompt, and unbuilt feature plans (Case entity, permissions, entry-type fields, image variants).

## Roadmap — near future

- [ ] Add `gd` to the Dockerfile, rebuild, clear the 25 media test failures; stub `Storage::fake()` where needed <!-- id:a2 cu:123kjkdhp5b -->
- [ ] Add + register an `EntryPolicy` (owner `update`) — fixes the always-403 entry upload; with a test <!-- id:b2 cu:123kjkdhp5c -->
- [ ] Fix the paginated-API 500: `collect($paginator->items())->map(...)`; add a `?per_page=` test <!-- id:c1 cu:123kjkdhp5d -->
- [ ] Hash API keys (store sha256, unique index, look up by hash); migration re-issues keys <!-- id:b1 cu:123kjkdhp5e -->
- [ ] Lock down CORS (drop `'*'`, pin the real frontend origins) <!-- id:b3 cu:123kjkdhp5f -->
- [ ] CI that stays free (GitHub Actions free tier / Coolify): `composer install`, `php artisan test`, Pint + PHPStan <!-- id:a3 cu:123kjkdhp5g -->
- [ ] Package updates (`composer/npm outdated`) + adopt packages that delete code (larastan, spatie permission/backup/medialibrary) <!-- id:a5 cu:123kjkdhp5h -->

## Roadmap — far future

- [ ] DO → Coolify: reproducible image, domain + SSL, secrets, script-based data move (a few hundred rows, not a heavy migration), backups, rollback on failed deploy <!-- id:e cu:123kjkdhp5j -->
- [ ] Move permissions to `spatie/laravel-permission` (cheap prep so tiers later = "a role with limits") <!-- id:d1 cu:123kjkdhp5k -->
- [ ] Account tiers — deferred: a product/pricing/marketing decision, not a build yet <!-- id:d2 cu:123kjkdhp5m -->
- [ ] Image variants pipeline / `AlbumImageVariant` (`docs/IMAGE_PROCESSING_PLAN.md`) <!-- id:f1 cu:123kjkdhp5n -->
- [ ] Case-study / portfolio entity (`docs/CASE_ENTITY_IMPLEMENTATION_PLAN.md`) <!-- id:f2 cu:123kjkdhp5p -->
- [ ] "Project" entity type (`docs/ENTITY_FEATURE_FOR_FRONTEND.md`) <!-- id:f3 cu:123kjkdhp5q -->
- [ ] Deep nested entry-type fields (validator only checks one level today) <!-- id:f4 cu:123kjkdhp5r -->
- [ ] Write API for entries + API media upload (unblocks the Image Colors integration) <!-- id:f5 cu:123kjkdhp5t -->

---

## Diary

### 2026-09-06 — first cockpit + test-infra fix + doc cleanup
- Added a `json` field type + `ImageColorsSeeder` (3 entry types, granted to itamar@gilboa.net); 6 green service tests.
- Fixed the env-shadowing bug with `tests/bootstrap.php`: suite 76 → 168 passing. Runtime unaffected.
- Wrote `docs/adversarial-review.md` (core-flow findings) and `docs/vams-renewal-plan.html` (roadmap v3 with a failure-chain diagram).
- Doc sweep: deleted 6 AI-scratch files, moved 12 planning docs into `docs/`, fixed README (Laravel 11→12, dead link). Root now holds only README.md + this cockpit.
- Reconstructed a `docker-compose.yml` I accidentally deleted via branch-hopping (rebuilt from the running containers; no data lost) and gitignored the stray `vams` sqlite file.
- Left red: 25 `gd` image tests, the whole security backlog. Nothing pushed; all on `AI-REFACTOR`.
