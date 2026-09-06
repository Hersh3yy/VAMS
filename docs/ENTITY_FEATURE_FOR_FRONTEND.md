# Entity / Entry Feature — Frontend Reference

This doc describes the **entity system** in VAMS so the frontend can list and handle all content types. A **Project** entity type does not exist yet; this describes what does exist and how a future “project” could fit.

---

## 1. What is an “entity”?

An **entity** is a user-owned content type that:

- Extends `BaseEntity` (shared: `title`, `description`, `user_id`, UUID, activity logging).
- Has its own table, routes, controller, service, and policies.
- Can have media (images/videos) and optional extra fields.

All entities are **scoped to the authenticated user** in the web app; the API can expose a filtered/public subset (e.g. published only).

---

## 2. Entity types that exist today

| Type     | Slug/Route | Table         | Purpose |
|----------|------------|---------------|---------|
| **Album**  | `albums`   | `albums`      | Ordered collection of images/videos with optional cover; can be published. |
| **Mosaic** | `mosaics`  | `mosaics`     | Multi-column grid of items (image, video, text, or embedded album). |
| **Entry**  | `entries`  | `entries`     | Typed content items (e.g. “I AM” statements, blog-style) with structure defined by **Entry Type**. |

There is **no “Project” entity or table yet**. Migration from another CMS may use “project” as a concept; see §5 for how that can map.

---

## 3. Common entity contract (for frontend)

Every entity type provides at least:

- **id** (UUID)
- **title**
- **description** (nullable)
- **user_id** (owner)
- **created_at**, **updated_at**
- **Route param name**: singular (e.g. `album`, `mosaic`, `entry`) and plural (e.g. `albums`, `mosaics`, `entries`).

So the frontend can treat “list of entity types” as: **Album**, **Mosaic**, **Entry**, and later **Project** when added.

---

## 4. Per-type details (for listing and UI)

### 4.1 Album

- **Fields**: `title`, `description`, `order`, `cover_image_path`, `user_id`, `published`.
- **Media**: Many images (`album_images`), many-to-many `media` (with pivot `order`).
- **Validation**: `title` required; optional `description`, `cover_image`, `published`.
- **Scopes**: `published` (where `published === true`).
- **Routes**: index, create, show, edit, store, update, destroy; image CRUD and reorder under album.

Use for: galleries, ordered image/video sets. Good candidate to map “project” from old CMS if project = gallery.

### 4.2 Mosaic

- **Fields**: `title`, `description`, `columns` (2–5), `display_settings` (JSON), `user_id`.
- **Media**: Many `mosaic_items` (each has `type`: `image` | `video` | `text` | `album`, plus `content`, optional `album_id`); many-to-many `media` with pivot order.
- **Validation**: `title` required; optional `description`, `columns`, `display_settings`.
- **Routes**: index, create, show, edit, store, update, destroy; item CRUD and reorder; media upload.

Use for: grid layouts mixing media and albums. Could map “project” if project = grid-based layout.

### 4.3 Entry (and Entry Types) ← main focus for “entry” / migration

- **Fields**: `title`, `content` (JSON), `status` (`draft` | `published`), `published_at`, `order`, `user_id`, `entry_type_id`.
- **Entry Type** (admin-defined): `id`, `name`, `slug`, `description`, `field_config` (array of field definitions), `is_active`.
- **Content**: Driven by `entry_type.field_config` (e.g. `statement` for “I AM”). Entry images are keyed by `field_name`.
- **Validation**: `title` required; `content` required; optional `status`, `order`; entry type must exist.
- **Scopes**: `published`, `draft`.
- **Routes**: entry CRUD + reorder; entry-types CRUD under admin.

**Entry Type `field_config` shape** (for dynamic forms):

- Each item: `name`, `type` (e.g. `textarea`), `label`, `required`, `placeholder`, and optional e.g. `rows`.
- Frontend can build forms and display content from `entry.content` using these definitions.

So **possibilities for a “project”** on the entry side: add an entry type like “Project” with its own `field_config` (e.g. summary, link, status). Existing data migrated from another CMS could be imported as entries of type “Project” once that type exists.

---

## 5. How a “Project” type can fit (for migration)

You have existing data to migrate and no project table yet. Two main options:

1. **Project as a new BaseEntity (new table `projects`)**  
   - Add `Project` model extending `BaseEntity`, with its own migrations, controller, service, routes.  
   - Use when “project” has a distinct structure (e.g. dates, status, links, sub-items) that doesn’t match albums/mosaics/entries.  
   - Migration script would create `Project` records and any related media.

2. **Project as an Entry Type**  
   - Create an entry type (e.g. name “Project”, slug `project`) with a `field_config` that matches the migrated fields.  
   - Migrate old “projects” as `Entry` rows with `entry_type_id` = that type and `content` + `entry_images` as needed.  
   - No new entity table; reuse entry list/detail UI and entry-type-based forms.

**Recommendation**: If the old CMS “project” is mostly title + description + media + a few extra fields, **Entry Type “Project”** is the fastest path. If it has complex relations or very different lifecycle (e.g. project phases, tasks), add a **Project** entity and table.

---

## 6. API surface (for frontend)

- **Web (Inertia)**: Entity list/create/show/edit under `/albums`, `/mosaics`, `/entries`. Entry types under admin (e.g. `/entry-types`).
- **API**: Prefix routes for `albums`, `mosaics`, `entries`; API key auth; typically returns published or scoped data.

When you add Project:

- If **new entity**: add `/projects` (and optional API prefix).
- If **entry type**: use existing `/entries` and `/entry-types`; frontend filters or labels by `entry_type.slug === 'project'`.

---

## 7. Summary for frontend

- **Entity types to list today**: Album, Mosaic, Entry (and optionally “entry types” for entries).
- **Common fields**: id (UUID), title, description, user_id, timestamps; each type has its own extra fields and media.
- **Project**: Not implemented yet. Decide between new “Project” entity vs “Project” entry type; then migration can map old data into that model.
- **Entry types**: Define what an “entry” can be (e.g. I AM, Blog, Project); frontend uses `field_config` to build forms and display `content`.

If you tell me whether the legacy “project” is more like a gallery, a grid, or a form-based record, I can suggest a concrete mapping (Album vs Mosaic vs Entry type) and a minimal API shape for the frontend.
