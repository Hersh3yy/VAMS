# VAMS — adversarial review of the core Entry flow

Status: review, 2026-09-06. Scope: the request/response path an external app (e.g. the Image Colors app) uses — API-key read of Entries — plus the web write path and media upload. Every claim carries a `file:line`.

## The flow, as built

**API read** (`GET /api/v1/entries`): `routes/api.php:22` wraps the group in `ValidateApiKey` + `throttle:60,1`, prefix `v1`. `ValidateApiKey::handle` (`ValidateApiKey.php:19`) reads `X-API-Key`, does `User::where('api_key',$key)->first()` (`:28`), checks `is_approved` (`:35`), then `$request->setUserResolver(...)` (`:40`). → `Api/EntryController::indexWithApiKey` (`:29`) reads `$request->user()`, `allowedEntryTypes()` (`User.php:115`), caps `per_page` at 100 (`:42-44`), calls `EntryService::getEntriesForApi($user,null,$perPage)`. Service (`EntryService.php:95`) scopes `$user->entries()->whereIn('entry_type_id',…)->published()->with(['entryType','images'])`, then `paginate()->through($format)` or `get()->map($format)`; `formatEntryForApi` (`:155`) shapes the array. Response via `BaseApiController::successPaginated` (`:40`) or `success` (`:17`).

**Web create** (`POST /entries`, `routes/web.php:55`, `auth,verified`): `EntryController::store` validates, checks `hasEntryTypePermission`, runs content through `EntryValidationService::validateContent`, computes `max('order')+1`, `$user->entries()->create([...])`.

**Media upload** (`POST /media/upload`, web): `MediaUploadController::upload` loops `$request->file('media')`, `ImageService::storeImage`, `createEntryImageRecord` which gates on `Gate::allows('update',$entry)`.

## Ranked findings

| # | Severity | Finding | Evidence | Fix |
|---|---|---|---|---|
| 1 | **Critical** | Entry media upload is dead-authorized: it gates on `Gate::allows('update',$entry)` but **no `EntryPolicy` exists** (only Album/Mosaic registered in `AppServiceProvider`), so the gate returns false → every entry image upload 403s. | `MediaUploadController.php:246`; `AppServiceProvider.php:34` | Add + register `EntryPolicy::update` (owner check) |
| 2 | **Serious** | Paginated API responses 500. `successPaginated` calls `$paginator->items()->map(...)`, but `LengthAwarePaginator::items()` returns a **plain array** (no `->map`). Any `?per_page=` request throws; the non-paginated path masks it. | `BaseApiController.php:49` | `collect($paginator->items())->map(...)` |
| 3 | **Serious** | API key stored **plaintext**, compared with a plain `where`, column has **no unique index**. A DB read exposes every key; full table scan per request. | `ValidateApiKey.php:28`; `User.php:74,158`; migration `:28` | Store `hash('sha256',$key)`, look up by hash, unique index; show raw key once |
| 4 | **Serious** | CORS `'*'` combined with `supports_credentials=true` on `api/*`, plus Sanctum stateful — origin-reflection exposure. | `config/cors.php:29,40` | Drop `'*'`, pin the real frontend origins |
| 5 | Warning | `Auth::user()`/`auth()` return **null** on API-key requests (only the request user-resolver is set, no guard). `BaseApiController::user()/userOwnsModel/validateOwnership` all use `Auth::user()` → NPE if ever called on the API path. | `ValidateApiKey.php:40`; `BaseApiController.php:132,154` | Use `$request->user()`, or set a proper guard |
| 6 | Warning | Unbounded read: with no `per_page`, `getEntriesForApi` returns the user's **entire** entry set; `per_page<=0` poisons `paginate()`. | `EntryService.php:121` | Always paginate / floor per_page at 1 |
| 7 | Warning | Content validation is shallow and silently drops data. `validateContent` returns `$validator->validated()`, keeping only enumerated keys; `repeatable`/`object` validate one level of scalars only; a nested `object`/`repeatable` type is coerced to `'string'` via the `default` arm. Arrays-of-objects deeper than one level pass unchecked. The new `json` type is pure passthrough (intentional). | `EntryValidationService.php:23,47,80,142` | Recurse for nesting, or document the contract per type |
| 8 | Warning | `reorder()` runs N `UPDATE`s in a loop with no transaction — a mid-loop failure leaves partial ordering. Ownership *is* scoped, so no cross-user write. | `EntryService.php:186` | Wrap in a transaction, or one `CASE` update |
| 9 | Note | `user_id` in `Entry::$fillable` — classic mass-assignment footgun (not currently exploitable; creates are explicit). | `Entry.php:15` | Remove from `$fillable` |
| 10 | Note | `showWithApiKey` returns 403 (not 404) when the user owns the entry but lacks type permission — leaks existence. | `Api/EntryController.php:121` | Return 404 for the not-permitted case |

## Design-pattern adherence (refactoring.guru)

- **Refused Bequest** ([link](https://refactoring.guru/smells/refused-bequest)): `EntryService` extends `BaseEntityService` but overrides/replaces most of it; the base `getById` even checks a non-existent `published` *property* (`BaseEntityService.php:72`) while Entry uses `status`/`published_at`. `EntryController` (web) overrides most of the `BaseEntityController` **Template Method** ([link](https://refactoring.guru/design-patterns/template-method)). The base classes are a template the main entity largely bypasses.
- **Duplicate Code** ([link](https://refactoring.guru/smells/duplicate-code)): `Api/MediaController::upload/delete` ≈ `MediaUploadController::handleSpaUpload/delete`.
- **Dead Code** ([link](https://refactoring.guru/smells/dead-code)): `Api/MediaController` (routes commented, `api.php:60`), `MediaService` (uses `public` disk while the app uses `spaces`), the `createBlog/News/MosaicMediaRecord` throw-stubs (`MediaUploadController.php:270`).
- **Repeated Switch** ([link](https://refactoring.guru/smells/switch-statements)): four `switch($entityType)` in `MediaUploadController` — Replace Conditional with Polymorphism.
- **Primitive Obsession** ([link](https://refactoring.guru/smells/primitive-obsession)): `status`/`entity_type` as bare strings (no enum), ids as raw strings.
- **Large Class** ([link](https://refactoring.guru/smells/large-class)): `ImageService` (497 lines) mixes storage, WebP/GD encoding, YouTube/Vimeo scraping, and placeholder drawing.
- **Repository pattern**: absent by Laravel convention; services *are* the data layer and must own scoping (they mostly do).

## Deep-module assessment

- **`EntryService`** is the deepest module: `getEntriesForApi/getEntryForApi` present a small `(User, ?slug, ?perPage)` interface over real work (permission scoping + published filter + eager-load + format), and take `$user` explicitly → testable. But `getAllEntries/reorder` reach for `Auth::user()` — static coupling that is harder to test and inconsistent with the injected-`$user` style.
- **`EntryValidationService`** is **shallow**: a thin adapter over Laravel's `Validator` whose interface hides a leaky, incomplete implementation (no deep nesting, silent key-dropping). Callers must know its blind spots — information leakage. It *is* injected, so the seam exists; the depth does not.
- **Hardest to test:** `ImageService` (static GD + `Storage`/`Http` facades, temp files — the missing `gd` extension is why ~19 media tests throw `LogicException`), and the paginated response path (needs a real paginator to surface finding #2).

## Test-suite state (2026-09-06)

After the env-shadowing fix (`tests/bootstrap.php`), the suite is **168 passing / 25 failing**. The 25 are all `LogicException` in media-upload tests — the container lacks the `gd` PHP extension, so `ImageService`'s image code throws. Installing `gd` in the image (and stubbing storage in those tests) is the next step; tracked in the roadmap.
