# VAMS Architecture Audit & Implementation Roadmap

## 1. Architectural Overview
VAMS is currently a **Monolithic Laravel Inertia** application with a split personality:
- **CMS Side (Web):** Uses Inertia.js for a SPA-like experience within the Laravel monolith.
- **Serving Side (API):** Provides read-only JSON endpoints for external frontends.

### Key Observations:
- **Service Layer:** Exists and is used by both Web and API controllers. This is a strong foundation.
- **Generic Base Controllers:** Both Web (`BaseEntityController`) and API (`BaseApiController`) use generic patterns to reduce boilerplate.
- **Database:** Migrated to MySQL (via Sail) with a clean schema, though migration ordering required manual correction.

---

## 2. API Audit: "Monolith to Clean Service-Driven"
While the current setup is functional, the "API" feels like a secondary concern rather than a first-class citizen.

### Identified Weaknesses:
- **Logic Leakage:** Some business logic (like published-only filtering) is handled inside API controllers instead of being fully encapsulated in the Service or Model Scopes.
- **Resource Duplication:** The API manually maps data (`formatAlbumForApi`) instead of using standard **Laravel API Resources**.
- **Auth Inconsistency:** External frontends use `api_key` while the CMS uses session-based auth.

### Optimization Tips (For Tomorrow):
1.  **Switch to Eloquent Resources:** Replace manual array formatting in Services with `AlbumResource`. This centralizes the "Serving" logic and makes it easier to version.
2.  **Model Scopes for Consistency:** Ensure `published()` and `forUser($user)` scopes are used everywhere to avoid leaking raw query logic into Controllers.
3.  **Strict Service Boundaries:** The Controller should only handle the Request/Response. All database logic, filtering, and formatting should reside in the Service layer or Resources.
4.  **Route Versioning:** Even if it's internal, prefixing with `/api/v1/` protects your external consumers from breaking changes.

---

## 3. Frontend Audit: "Atomic Design Consolidation"
The project has a solid `resources/js/Components` structure (`atoms`, `molecules`, `organisms`), but some boundaries are blurred.

### Current Component Distribution:
- **Base:** Foundation (Button, Input, Modal).
- **Atoms:** Specialized icons and small UI cards (`EntityCard`, `UploadStatusIcon`).
- **Molecules:** Functional groups (`FormField`, `ConfirmationDialog`).
- **Organisms:** Complex sections (`ContentGrid`, `AlbumForm`).

### Implementation Strategy (The "Hiren Ninja" Standard):
- **Base vs Atoms:** Move generic UI primitives (like `Badge` or `StatCard`) from `Base` to `atoms` if they aren't fundamental HTML wrappers.
- **Logic Placement:** Keep business logic *out* of `molecules`. If a component needs to "know" about an Album ID to fetch something, it's likely an `organism` or a `page` component.
- **TypeScript Integration:** Your build logs show `vue-tsc` is running. Ensure all new components in the Atomic hierarchy have strictly typed Props and Emits.
- **Naming Convention:** Prefix "Domain" components (e.g., `AlbumItemCard`) to distinguish them from "Generic" atoms (e.g., `BaseCard`).

---

## 4. Immediate Next Steps (Agentic Head Start)
To give you a head start tomorrow, I have prepared the following:
1.  **Sail is Ready:** Database, Redis, and Vite are configured. Run `./vendor/bin/sail up -d` to start.
2.  **Migration Fix:** I moved the "initial migration" to `2024_01_01` so it runs first. Fresh installs will work perfectly.
3.  **Audit Log:** This document (`ARCH_AUDIT.md`) serves as your strategic map.

### Proposed Task List for Tomorrow:
1.  **Refactor API Formatting:** Create `app/Http/Resources` for main entities.
2.  **Service Cleanup:** Move the remaining query logic from `app/Http/Controllers/Api/AlbumController.php` into `AlbumService`.
3.  **Atomic Linting:** Audit `molecules` to ensure no direct Service calls (use props/events instead).
