# VAMS - Visual Album Management System

VAMS is a headless CMS for visual content: albums, mosaics, and structured entries. It serves both as an admin interface and as a read-only API backend for existing websites.

## Core Features

- **Album Management**: Create and organize image collections with rich metadata
- **Landing Page Builder**: Visual drag-and-drop mosaic builder with desktop/mobile layouts
- **Entries**: Dynamic entry types (text, images, relations) for portfolio-style content
- **API-First Design**: Read-only API key access for external frontends
- **Theme Customization**: Per-user theming and branding options

## Tech Stack

- PHP 8.4 / Laravel 13 (pinned to 8.4 for the DigitalOcean buildpack)
- PostgreSQL (containerized locally, managed in production)
- Docker & Docker Compose
- Vue.js 3 + Inertia (admin)
- Pest 4 for tests
- Scramble for API documentation

## Development Setup

Local app port defaults to **8080** (`APP_PORT` in `.env`).

1. Clone the repository:
   ```bash
   git clone https://github.com/Hersh3yy/VAMS.git
   cd VAMS
   ```

2. Create the environment file:
   ```bash
   cp .env.example .env
   ```

3. Install dependencies and build:
   ```bash
   docker compose run --rm api composer install
   docker compose build
   ```

4. Generate the app key:
   ```bash
   docker compose run --rm api php artisan key:generate
   ```

5. Start the stack:
   ```bash
   docker compose up -d
   ```

6. Optional — migrate and seed:
   ```bash
   docker compose run --rm api php artisan migrate:fresh --seed
   ```

PHP/Artisan commands always go through Docker:

```bash
docker compose exec api php artisan <command>
```

## API

External frontends authenticate with an `X-API-Key` header. Routes are read-only.

- Guide: [`docs/API_FRONTEND_GUIDE.md`](docs/API_FRONTEND_GUIDE.md)
- Live docs: http://localhost:8080/docs/api
- OpenAPI spec: http://localhost:8080/docs/api.json
- Telescope (local): http://localhost:8080/telescope

## Testing

The `api` image is built with `composer install --no-dev`, so Pest is not inside it. Use a full Composer install, then:

```bash
docker run --rm -v "$(pwd)":/app -w /app composer:2 composer install
docker compose run --rm api php artisan test
```

Tests use in-memory SQLite (`phpunit.xml`), not the Postgres database in `.env`.

## Documentation

Living docs live in [`docs/`](docs/):

- [`docs/API_FRONTEND_GUIDE.md`](docs/API_FRONTEND_GUIDE.md) — API key auth and consumer endpoints
- [`docs/ATOMIC_DESIGN.md`](docs/ATOMIC_DESIGN.md) — Vue component organization
- [`docs/COOLIFY_DB_MIGRATION_GUIDE.md`](docs/COOLIFY_DB_MIGRATION_GUIDE.md) — DigitalOcean → Coolify Postgres cutover
- [`docs/IMAGE_PROCESSING_PLAN.md`](docs/IMAGE_PROCESSING_PLAN.md) — planned image variants work
- [`docs/VIDEO_THUMBNAIL_FEATURES.md`](docs/VIDEO_THUMBNAIL_FEATURES.md) — video thumbnails and the fix command

File storage remains DigitalOcean Spaces (`DO_SPACES_*`). That is separate from the database cutover.

## Still on the agenda

- Image variants (WebP/JPEG sizes) — see the image processing plan
- Mosaic cropping and finer tile control
- Queue-based image processing when a single instance is no longer enough
