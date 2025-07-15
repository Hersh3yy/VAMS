# Controller Explanation

## Album Controllers Overview

There are three main controllers that handle album-related functionality:

### 1. AlbumController.php (Web Controller)
**Purpose**: Handles web-based album operations for authenticated users
**Main Functions**:
- CRUD operations (Create, Read, Update, Delete) for albums
- Renders Inertia.js pages for web interface
- Handles cover image uploads
- User authorization (ensures users can only access their own albums)
- Returns Inertia responses for Vue.js frontend

**Key Methods**:
- `index()` - Shows all user's albums
- `show()` - Shows specific album with images
- `edit()` - Shows album edit form
- `store()` - Creates new album
- `update()` - Updates album details and cover image
- `destroy()` - Deletes album

### 2. Api/AlbumController.php (API Controller)
**Purpose**: Handles API-based album operations for both authenticated and public access
**Main Functions**:
- Provides JSON API responses
- Public album access with API keys
- Handles album display settings for API consumers
- Supports different response formats (standard, Strapi)
- Manages album images through API

**Key Methods**:
- `index()` - Returns all albums as JSON
- `show()` - Returns specific album with images as JSON
- `showWithApiKey()` - Public access with user display settings applied
- `showByTitle()` - Find albums by title
- `formatAlbumWithUserSettings()` - Applies user's display preferences
- Image CRUD operations for API consumers

### 3. AlbumImageController.php (Image Management Controller)
**Purpose**: Handles individual image operations within albums
**Main Functions**:
- Upload images to albums
- Update image metadata (title, caption, alt text, etc.)
- Reorder images within albums
- Delete images from albums
- Handle video URL additions to albums

**Key Methods**:
- `store()` - Upload multiple images to an album
- `update()` - Update image metadata
- `destroy()` - Delete specific image
- `reorder()` - Change image order within album
- `storeVideo()` - Add video URLs to albums

## Data Flow

1. **Web Interface**: User interacts with Vue.js components → AlbumController → Database
2. **API Access**: External apps use API keys → Api/AlbumController → Database (with user display settings)
3. **Image Management**: Both web and API use → AlbumImageController → ImageService → Storage

## User Display Settings

The album display settings (configured in Profile) affect:
- **Api/AlbumController**: When serving albums via API, it applies user's display preferences
- **AlbumService**: Formats album data according to user settings
- **Frontend**: Album viewers see only the fields the user has enabled (title, caption, author, etc.)

This separation allows:
- Clean web interface for album management
- Robust API for external integrations
- Granular image management
- User-controlled data presentation 