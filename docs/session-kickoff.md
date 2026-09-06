# VAMS — session kickoff prompt

Paste the block below into a fresh Claude Code session (opened in `koala/VAMS`) to resume the VAMS production-readiness work with full context.

---

You are resuming work on **VAMS** (`/Users/hirenbudhrani/Documents/koala/VAMS`), a Laravel 12 + PostgreSQL 15 headless CMS (Inertia + Vue admin, Sanctum, read-only API-key API at `/api/v1`). It is deployed and in a **DigitalOcean → Coolify migration**. It is also the planned persistence backend for the separate **Image Colors** app (`koala/img-clrs`).

**Environment**
- Runs in Docker. Compose service is **`api`** (not `app`), Postgres db is `main` locally (`vams_local` in the container). Run artisan as: `docker compose exec api php artisan …`. The `gd` PHP-extension warning on every call is a known missing extension (see below) — harmless for non-image commands.
- `docker-compose.yml` is **gitignored / local-only**; Coolify builds from the `Dockerfile`. If it goes missing it can be reconstructed from the running containers (`docker inspect vams-api vams-postgres`). Data lives in the `vams_postgres_data` volume.
- Active branch: **`AI-REFACTOR`** (= `main` + "refactor phase 1" + the work below). It is ahead of and healthier than `main`. Decide early whether it becomes trunk.

**Progress so far (committed on `AI-REFACTOR`, not pushed)**
1. `feat: image-colors entry types + json field type` — added a `json` passthrough field type to `EntryValidationService` + the admin whitelist; `ImageColorsSeeder` provisions 3 entry types (`parent-colors`, `preset`, `processed-image`) and grants them to `itamar@gilboa.net`. Run: `docker compose exec api php artisan db:seed --class=ImageColorsSeeder`.
2. `test: fix testing-env shadowing …` — the container bakes `APP_ENV=local`, which shadowed phpunit's `testing` env (even `force="true"` and `.env.testing` couldn't win), so `runningUnitTests()` was false, CSRF was never skipped, and every non-GET web test 419'd. `tests/bootstrap.php` sets the env in-process before Laravel loads. **Suite went 76 → 168 passing.** Runtime env is unchanged (still `local` outside tests) — online deploy unaffected.
3. Added `tests/Feature/EntryValidationServiceTest.php` (6 passing, service-level, no HTTP).

**Read these first**: `docs/vams-renewal-plan.html` (the roadmap — tracks A–F), `docs/adversarial-review.md` (core-flow findings with file:line).

**Test state**: `docker compose exec api php artisan test` → **168 passed / 25 failed / 3 skipped**. All 25 failures are `LogicException` in media-upload tests, caused by the **missing `gd` extension** in the image (`ImageService` WebP/GD code). That is roadmap item **A2**.

**Do next (roadmap order)**:
- **A2** — add `gd` to the `Dockerfile`, rebuild, watch the 25 media failures fall; stub `Storage::fake()` where tests still touch disk.
- **B2** — add + register an `EntryPolicy` (`update` = owner). Entry media upload currently always 403s because the gate has no policy (`MediaUploadController.php:246`). Add a test.
- **C1** — fix the paginated-API 500: `BaseApiController::successPaginated` calls `$paginator->items()->map(...)` but `items()` is a plain array — `collect(...)` it; add a `?per_page=` test.

**Security backlog before public/tiered traffic** (track B): hash API keys (stored plaintext, unindexed, `ValidateApiKey.php:28`); lock down CORS (`'*'` + `supports_credentials` in `config/cors.php`); drop `user_id` from `Entry::$fillable`.

**House rules for this project**: keep the running/online system working; only migrate when it genuinely helps. Use Docker for all local runs. The learning-related Image Colors types (`feedback`, `knowledge-base`) are deferred until that app's learning stack is reviewed — don't build them yet. Verify claims by running code (tests/tinker), not assertion.

---
