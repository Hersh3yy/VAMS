# API & Frontend Guide

## Overview
This application has a clean separation between frontend operations and API access:

**Frontend (Inertia/Vue)**: Session-based authentication, full CRUD operations
**API**: Read-only access for external frontends using API keys

## Architecture

### Frontend (Inertia/Vue)
- **Authentication**: Session-based (login/logout)
- **Routes**: Web routes (`/albums`, `/mosaics`, etc.)
- **Operations**: Full CRUD - Create, Read, Update, Delete
- **Usage**: Internal application interface

### API (External Frontends)
- **Authentication**: API key via `X-API-Key` header
- **Routes**: API routes (`/api/albums`, `/api/mosaics`, etc.)
- **Operations**: Read-only - Get albums/mosaics for display
- **Usage**: External websites wanting to display your content

## API Endpoints

### Authentication
All API endpoints require an API key sent in the `X-API-Key` header.

### Albums
- `GET /api/albums` - Get all albums
- `GET /api/albums/{id}` - Get specific album by ID
- `GET /api/albums/by-title/{title}` - Get specific album by title

### Mosaics
- `GET /api/mosaics` - Get all mosaics
- `GET /api/mosaics/{id}` - Get specific mosaic by ID
- `GET /api/mosaics/by-title/{title}` - Get specific mosaic by title

### Testing
- `GET /api/test` - Test API key authentication

## Usage Examples

### JavaScript/Fetch
```javascript
// Get all albums
fetch('/api/albums', {
    headers: {
        'X-API-Key': 'your-api-key-here'
    }
})
.then(response => response.json())
.then(data => console.log(data));

// Get specific album
fetch('/api/albums/by-title/my-album', {
    headers: {
        'X-API-Key': 'your-api-key-here'
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### cURL
```bash
# Get all albums
curl -H "X-API-Key: your-api-key-here" \
  http://your-domain.com/api/albums

# Get specific album
curl -H "X-API-Key: your-api-key-here" \
  http://your-domain.com/api/albums/by-title/my-album
```

## Why This Structure?

### Security
- **API**: Read-only prevents external sites from modifying your content
- **Frontend**: Full control with proper authentication

### Performance
- **API**: Lightweight, focused on data delivery
- **Frontend**: Rich interface with all management features

### Separation of Concerns
- **API**: Simple data access for external consumption
- **Frontend**: Complex UI operations and content management

## Getting Your API Key

1. Log into your account
2. Go to Profile → API Key section
3. Copy your API key
4. Use it in the `X-API-Key` header for all API requests

## CORS Configuration

If you're calling the API from a different domain, make sure CORS is properly configured in your `config/cors.php` file. 