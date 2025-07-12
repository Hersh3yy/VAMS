# VAMS - Visual Album Management System

VAMS is a headless CMS specifically designed for visual content management, offering a user-friendly interface for managing albums, landing page layouts, and visual assets. It's built to serve both as a standalone system and as a content backend for existing websites.

## Core Features

- **Album Management**: Create and organize image collections with rich metadata
- **Landing Page Builder**: Visual drag-and-drop mosaic builder with desktop/mobile layouts
- **API-First Design**: Built to serve content to any frontend
- **Theme Customization**: Per-user theming and branding options
- **Responsive Layouts**: Separate desktop and mobile layout management

## Tech Stack

- PHP 8.3 with strict typing
- Laravel 11
- PostgreSQL
- Docker & Docker Compose
- Vue.js 3 (Admin Interface)
- PHPUnit for testing
- Scramble for API documentation

## Architecture

### Controllers
- **BaseController**: Common functionality for web controllers (Inertia)
- **BaseApiController**: Standardized API responses and error handling
- **Strict Typing**: All controllers use PHP 8+ type declarations
- **Service Pattern**: Business logic separated into dedicated service classes

### API Response Format
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // response data
  }
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    // validation errors (optional)
  }
}
```

### Service Layer
- **AlbumService**: Handles album business logic with strict typing
- **MosaicService**: Manages mosaic operations and formatting
- **ImageService**: Processes image uploads and transformations
- **MediaService**: General file management utilities

### Authentication & Authorization
- **Sanctum Authentication**: For API access with tokens
- **API Key Middleware**: For public website integration
- **Ownership Validation**: Centralized authorization checks
- **User Scoped Queries**: Automatic filtering by authenticated user

### Key Improvements (v1.0)
- ✅ **Strict Typing**: All methods use PHP 8+ type declarations
- ✅ **Base Controllers**: Consistent response patterns and authorization
- ✅ **Service Pattern**: Business logic separated from controllers
- ✅ **API Standardization**: Unified response formats across all endpoints
- ✅ **Error Handling**: Comprehensive error logging and user-friendly messages
- ✅ **Code Deduplication**: Eliminated redundant patterns across controllers

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

### Protected API (Sanctum Token Required)
```
# Albums
GET    /api/albums                    # List user's albums
POST   /api/albums                    # Create new album
GET    /api/albums/{id}               # Get album details
PUT    /api/albums/{id}               # Update album
DELETE /api/albums/{id}               # Delete album
GET    /api/albums/by-title/{title}   # Get album by title

# Album Images
POST   /api/albums/{id}/images        # Upload image to album
PUT    /api/albums/{id}/images/reorder # Reorder images
PUT    /api/albums/{id}/images/{imageId} # Update image metadata
DELETE /api/albums/{id}/images/{imageId} # Delete image

# Mosaics
GET    /api/mosaics                   # List user's mosaics
POST   /api/mosaics                   # Create new mosaic
GET    /api/mosaics/{id}              # Get mosaic details
PUT    /api/mosaics/{id}              # Update mosaic
DELETE /api/mosaics/{id}              # Delete mosaic
GET    /api/mosaics/by-title/{title}  # Get mosaic by title
POST   /api/mosaics/{id}/media        # Upload media to mosaic

# User Resources
GET    /api/user/albums               # Get user's albums
GET    /api/user/mosaics              # Get user's mosaics
```

### Public API (API Key Required)
```
# Test Connection
GET    /api/public/test               # Verify API key

# Public Albums (with user display settings)
GET    /api/public/albums             # List albums
GET    /api/public/albums/{id}        # Get album
GET    /api/public/albums/by-title/{title} # Get album by title

# Public Mosaics (with user display settings)
GET    /api/public/mosaics            # List mosaics
GET    /api/public/mosaics/{id}       # Get mosaic
GET    /api/public/mosaics/by-title/{title} # Get mosaic by title
```

### Development Tools
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

## Next Version Features Wishlist

### Image Enhancement
- **New content types**: Blog article

### Image Enhancement
- **Image Resizing & Multiple Formats**: Automatic generation of responsive image sizes (thumbnail, medium, large) and modern formats (WebP, AVIF)
- **Smart Compression**: Intelligent image optimization with quality preservation

### Mosaic Builder Enhancements
- **Image Cropping & Positioning**: Fine-grained control over image framing within mosaic tiles
- **Zoom Controls**: Intuitive zoom and pan functionality for precise image positioning
- **Dynamic Tile Sizing**: Flexible tile dimensions with custom aspect ratios
- **Video Support**: Native video embedding and playback within mosaic layouts
- **Advanced Grid System**: More sophisticated layout options with custom breakpoints

### User Experience
- **Batch Operations**: Multi-select for bulk image editing and operations
- **Advanced Search**: Filter and search across albums by metadata, tags, and content
- **Keyboard Shortcuts**: Power-user navigation and editing shortcuts
- **Preview Mode**: Real-time preview of changes before publishing
- **User controlled theme**: Real-time preview of changes before publishing

### Performance & Technical
- **CDN Integration**: Built-in support for content delivery networks
- **Progressive Loading**: Lazy loading and progressive image enhancement
- **API Rate Limiting**: Enhanced API protection and usage analytics
- **Export/Import**: Backup and migration tools for content and settings

### Integrations
- **Third-Party Storage**: S3, Cloudinary, and other cloud storage providers
- **Webhook System**: Real-time notifications for content changes
- **Social Media Sync**: Direct publishing to social platforms
- **Analytics Integration**: Usage tracking and performance metrics

*Note: This wishlist represents planned enhancements for future releases. Features will be prioritized based on user feedback and project requirements.* user controlleed theme colors



TODO: