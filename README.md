# VAMS - Visual Album Management System

VAMS is a headless CMS specifically designed for visual content management, offering a user-friendly interface for managing albums, landing page layouts, and visual assets. It's built to serve both as a standalone system and as a content backend for existing websites.

## Core Features

- **Album Management**: Create and organize image collections with rich metadata
- **Landing Page Builder**: Visual drag-and-drop mosaic builder with desktop/mobile layouts
- **API-First Design**: Built to serve content to any frontend
- **Theme Customization**: Per-user theming and branding options
- **Responsive Layouts**: Separate desktop and mobile layout management

## Tech Stack

- PHP 8.5
- Laravel 13
- PostgreSQL everywhere (managed in production, containerized locally via Docker Compose)
- Docker & Docker Compose
- Vue.js 3 (Admin Interface)
- PHPUnit for testing
- Scramble for API documentation

## API Integration

### Getting Landing Page Content

```typescript
// Example response from GET /api/v1/sites/{site_id}/landing
{
  data: {
    id: "uuid",
    title: "Homepage",
    theme: {
      logo: "https://assets.vams.com/logos/site-logo.png",
      colors: {
        primary: "#FF0000",
        secondary: "#00FF00"
        // ... other theme settings
      }
    },
    mosaic: {
      desktop: [
        {
          id: "uuid",
          type: "image", // or "album"
          position: { x: 0, y: 0, width: 2, height: 2 },
          content: {
            image_url: "https://assets.vams.com/images/hero.jpg",
            title: "Welcome",
            description: "Our latest collection",
            link: "/collections/latest"
          }
        }
        // ... more items
      ],
      mobile: [
        // Mobile-specific layout
      ]
    },
    meta: {
      last_updated: "2024-03-10T15:30:00Z",
      version: 1
    }
  },
  cache: {
    ttl: 3600,
    etag: "abc123"
  }
}
```

### Implementation Guide

1. **Cache Integration**:
   ```javascript
   // Example client implementation
   async function getLandingContent(siteId) {
     const response = await fetch(`/api/v1/sites/${siteId}/landing`, {
       headers: {
         'If-None-Match': localStorage.getItem('landing-etag')
       }
     });
     
     if (response.status === 304) {
       return JSON.parse(localStorage.getItem('landing-content'));
     }
     
     const data = await response.json();
     localStorage.setItem('landing-etag', data.cache.etag);
     localStorage.setItem('landing-content', JSON.stringify(data));
     return data;
   }
   ```

## Development Setup

## Run Locally with Docker

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/work-it-out
   cd work-it-out
   ```

2. Create environment files:
   ```bash
   # Laravel directory
   cp .env.example .env
   ```

3. Install dependencies and build:
   ```bash
   docker compose run --rm api composer install
   docker compose build
   ```

4. Setup application:
   ```bash
   docker compose run --rm api php artisan key:generate
   ```

5. Start the application:
   ```bash
   docker compose up -d
   ```

6. Setup database with seed data (optional):
   ```bash
   docker compose run --rm api php artisan migrate:fresh --seed
   ```

## API Endpoints
- `GET /docs/api` - API Documentation UI
- `GET /telescope` - Development debugging dashboard

## Testing

The `api` container image is built with `composer install --no-dev`, so Pest/PHPUnit
aren't available inside it. Run tests with a dev install instead, e.g.:

```bash
docker run --rm -v "$(pwd)":/app -w /app composer:2 composer install
docker compose run --rm api php artisan test
```

Tests use an in-memory SQLite connection (see `phpunit.xml`), never the
PostgreSQL database configured in `.env`.

## API Documentation

Access the auto-generated API documentation:
- UI Documentation: http://localhost:8000/docs/api
- OpenAPI Spec: http://localhost:8000/docs/api.json

Development Tools: http://localhost:8000/telescope

## Documentation

Living docs live in [`docs/`](docs/):

- [`docs/API_FRONTEND_GUIDE.md`](docs/API_FRONTEND_GUIDE.md) — API key auth and consumer endpoints
- [`docs/ATOMIC_DESIGN.md`](docs/ATOMIC_DESIGN.md) — Vue component organization
- [`docs/COOLIFY_DB_MIGRATION_GUIDE.md`](docs/COOLIFY_DB_MIGRATION_GUIDE.md) — DigitalOcean → Coolify Postgres cutover
- [`docs/IMAGE_PROCESSING_PLAN.md`](docs/IMAGE_PROCESSING_PLAN.md) — planned image variants work
- [`docs/VIDEO_THUMBNAIL_FEATURES.md`](docs/VIDEO_THUMBNAIL_FEATURES.md) — video thumbnails and the fix command

PHP/Artisan commands always go through Docker: `docker compose exec api php artisan <command>`.

## 🚀 Development Roadmap

### ✅ **Completed (MVP-1)**
- Core album and mosaic management functionality
- API-first architecture with comprehensive documentation
- Admin interface with user management
- Digital Ocean deployment and file storage
- Atomic design foundation implementation

### 🔄 **Current Phase: Pre-Launch Preparation**
- **Testing Infrastructure**: Comprehensive Pest 4 test suite (Unit, Feature, API, E2E)
- **Frontend Architecture**: Complete atomic design refactoring
- **Code Quality**: 80%+ test coverage, performance optimization
- **Security**: Security audit and vulnerability assessment

### 📋 **Post-MVP-1: User Management & Subscriptions** (10 hours)
- Enhanced user registration/login flow with email verification
- Subscription tier system with payment integration (Stripe/Paddle)
- User access control based on subscription levels
- Social login integration (Google, GitHub)

### 📋 **Post-MVP-2: Content Expansion & Business Features** (10 hours)
- Multi-content type support (video, documents, custom types)
- Analytics dashboard with user activity tracking
- Content performance metrics and usage reporting
- Scalable architecture for future content type additions

### 🎯 **Future Enhancements**

#### **Image Enhancement**
- **Image Resizing & Multiple Formats**: Automatic generation of responsive image sizes and modern formats (WebP, AVIF)
- **Smart Compression**: Intelligent image optimization with quality preservation

#### **Mosaic Builder Enhancements**
- **Image Cropping & Positioning**: Fine-grained control over image framing within mosaic tiles
- **Dynamic Tile Sizing**: Flexible tile dimensions with custom aspect ratios
- **Video Support**: Native video embedding and playback within mosaic layouts

#### **Performance & Technical**
- **CDN Integration**: Built-in support for content delivery networks
- **Progressive Loading**: Lazy loading and progressive image enhancement
- **Export/Import**: Backup and migration tools for content and settings

#### **Integrations**
- **Third-Party Storage**: S3, Cloudinary, and other cloud storage providers
- **Webhook System**: Real-time notifications for content changes