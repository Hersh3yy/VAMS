# VAMS - Visual Album Management System

VAMS is a headless CMS specifically designed for visual content management, offering a user-friendly interface for managing albums, landing page layouts, and visual assets. It's built to serve both as a standalone system and as a content backend for existing websites.

## Core Features

- **Album Management**: Create and organize image collections with rich metadata
- **Landing Page Builder**: Visual drag-and-drop mosaic builder with desktop/mobile layouts
- **API-First Design**: Built to serve content to any frontend
- **Theme Customization**: Per-user theming and branding options
- **Responsive Layouts**: Separate desktop and mobile layout management

## Tech Stack

- PHP 8.3
- Laravel 11
- PostgreSQL
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

Run the test suite:
```bash
docker compose exec api php artisan test
```

## API Documentation

Access the auto-generated API documentation:
- UI Documentation: http://localhost:8000/docs/api
- OpenAPI Spec: http://localhost:8000/docs/api.json

Development Tools: http://localhost:8000/telescope
