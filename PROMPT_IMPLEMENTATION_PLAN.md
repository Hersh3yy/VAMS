# Implementation Plan & Weekend Pickup Prompt

Use this file when picking up the project. Run phases in order; after each phase run `php artisan test` and `vendor/bin/pint --dirty`.

**Stack:** Laravel 12, Inertia v2, Vue 3, Tailwind.

---

## Pre-flight

- Run `composer install` / `npm install` if needed.
- Run `php artisan test` and `vendor/bin/pint --dirty`.
- Read `atomic_design.md` for frontend structure.

---

## Completed (reference only)

- **Phase 1:** Web vs API media redundancy — single upload path via `MediaUploadController`; `ImageCollectionManager.vue` uses `route('media.upload')`; API media routes commented out.
- **Phase 2:** All failing tests fixed (album update validation, mosaic reorder, MosaicService items loading, AlbumImageTest RefreshDatabase, etc.).
- **Phase 5 (partial):** Duplicate `albums/UploadItem.vue` removed; `molecules/UploadItem.vue` is canonical; `resources/js/Components/README.md` added.
- **Phase 3 (partial):** Form Requests added for `MediaUploadController` (MediaUploadRequest, MediaDeleteRequest), `Admin\UserController` (StoreUserRequest, UpdateUserRequest), `Admin\EntryTypeController` (StoreEntryTypeRequest, UpdateEntryTypeRequest).
- **Phase 3 (complete):** ProfileController, EntryController, AlbumImageController use Form Requests; `storeVideo` uses `validated()`.
- **Phase 4 (complete):** ValidateApiKey `declare(strict_types=1)`, ProfileUpdateRequest `declare(strict_types=1)`, `/test-api` restricted to non-production, ActivityTest Mockery alias fix.
- **Phase 5 (complete):** Size-exception comments on 6 oversized domain components; hierarchy verified (no atom→organism imports).

---

## Phase 7: Docker & Node upgrade

- Upgrade Dockerfile to Node 24.
- Run the app in Docker and verify: `docker compose up`, `npm run build`, `php artisan test` inside container.

**Acceptance:** Docker workflow works end-to-end.

---

## Phase 8: Documentation

### 8.1 API & integration guide (for frontend developers)

**Audience:** Frontend devs (including noobs) connecting apps/sites to this CMS.

**Contents:** Overview, API key retrieval/setup, endpoints, request/response shapes, auth header, example requests (curl, fetch), "hello world" example, CORS/rate limits/errors.

**Deliverable:** `docs/API_AND_INTEGRATION_GUIDE.md` (or split into `API_QUICKSTART.md` + `API_REFERENCE.md` if long).

### 8.2 CMS usage guide (for content editors)

**Audience:** Non-technical people using the web interface.

**Contents:** Login, dashboard, albums (create/edit/delete, images, reorder, cover), mosaics, entries, image upload tips, help/support.

**Deliverable:** `docs/CMS_USER_GUIDE.md`.

### 8.3 Future features document (internal)

**Process:**
1. After writing the two guides, read them as intended audience (noob frontender, then content editor).
2. Note friction, missing features, UX gaps.
3. Compile into a prioritized future features list.
4. Store in `docs/FUTURE_FEATURES.md` (or `.gitignore` if private).

**Acceptance:** Both audience docs exist; future features doc exists and reflects real gaps.

---

## Phase 6: API polish & API/Web duplication

**Current state:**
- API routes: `/api/albums`, `/api/mosaics`, `/api/entries` (read-only, API key auth).
- Web routes: `/albums`, `/mosaics`, `/entries` (CRUD, session auth).
- `Api\*` controllers vs `App\Http\Controllers\*` — separate controllers, some duplication.
- Services format API responses: `formatAlbumForApi`, `formatEntryForApi`, `formatMosaicForApi`.
- Throttle: 60/min. Media routes commented out.

**Primary goal: reduce API/Web duplication**

- Some logic may be duplicated between `Api\AlbumController` and `AlbumController` (e.g. query, formatting).
- Options: extract shared logic into services, use traits, or have API controllers delegate to services that web controllers also use. Avoid duplication where it makes sense.
- Route versioning (`/api/v1/`) — optional; add if you want future v2 compatibility.

**Other Phase 6 options:**

- **Pagination** — Index endpoints return all records. Add `?page=1&per_page=15` for albums, mosaics, entries.
- **Eloquent API Resources** — Replace service `format*ForApi` with `AlbumResource`, `EntryResource`, `MosaicResource`. Can centralize formatting.
- **Media routes** — Decide whether to re-enable for external clients; keep API key auth if so.


---

## Execution order

1. **Phase 7** — Docker & Node 24.
2. **Phase 8** — Documentation: API guide, CMS user guide, future features doc.
3. ~~**Phase 6**~~ — Done.

---

## Last session summary (for next chat)

- **Completed:** Phases 3, 4, 5, 6. Phase 6: API/Web dedup — services now have `getAlbumsForApi`, `getMosaicsForApi`, `getEntriesForApi`; API controllers delegate to services; pagination via `?per_page=N`; route versioning `/api/v1/`.

---

## Codanna (MCP)

Codanna `find_symbol` / `search_symbols` may return no results or results from a different project (e.g. dragonfly). The index (`get_index_info`) reports 35k+ symbols but searches can hit the wrong workspace. **Fix:** Ensure Codanna is indexing the VAMS workspace. Run `codanna index` or re-index from the Codanna extension. If using multiple projects, verify the active workspace.

---

## Quick reference

- **Routes:** `routes/web.php`, `routes/api.php`
- **Media:** `MediaUploadController`, `ImageCollectionManager.vue`, `ImageService`
- **Atomic design:** `atomic_design.md`, `resources/js/Components/README.md`
- **Form Requests:** `app/Http/Requests/MediaUploadRequest.php`, `Admin/StoreUserRequest.php`, etc.
- **Tests:** `tests/Feature/`, `tests/Api/`
- **Docs (Phase 8):** `docs/API_AND_INTEGRATION_GUIDE.md`, `docs/CMS_USER_GUIDE.md`, `docs/FUTURE_FEATURES.md`
