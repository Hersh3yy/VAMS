# VAMS API Frontend Integration Guide

## Overview
This guide explains how to integrate the VAMS (Visual Asset Management System) API with a Nuxt.js frontend application.

## API Endpoints

All public API endpoints require an API key to be passed in the `X-API-Key` header.

### Base URL
```
https://your-vams-domain.com/api/public
```

### Authentication
All requests must include the API key in the header:
```javascript
headers: {
  'X-API-Key': 'your-api-key-here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

## Available Endpoints

### 1. Test Connection
**GET** `/api/public/test`

Test if your API key is valid and the connection is working.

**Response:**
```json
{
  "message": "API key authentication successful",
  "user": "Username",
  "timestamp": "2025-01-01T12:00:00.000000Z"
}
```

### 2. Get Album by Title
**GET** `/api/public/albums/by-title/{title}`

Retrieve an album by its title (URL encoded).

**Parameters:**
- `title` (string) - The album title (URL encoded)

**Response:**
```json
{
  "id": 1,
  "title": "My Album",
  "description": "Album description",
  "images": [
    {
      "id": 1,
      "filename": "image1.jpg",
      "path": "/storage/albums/image1.jpg",
      "alt_text": "Description",
      "order": 1
    }
  ],
  "user": {
    "name": "Owner Name"
  }
}
```

### 3. Get Album by ID
**GET** `/api/public/albums/{id}`

Retrieve an album by its ID.

**Parameters:**
- `id` (integer) - The album ID

### 4. Get Mosaic by Title
**GET** `/api/public/mosaics/by-title/{title}`

Retrieve a mosaic by its title (URL encoded).

**Parameters:**
- `title` (string) - The mosaic title (URL encoded)

**Response:**
```json
{
  "id": 1,
  "title": "My Mosaic",
  "description": "Mosaic description",
  "items": [
    {
      "id": 1,
      "title": "Item Title",
      "description": "Item description",
      "image_path": "/storage/mosaics/item1.jpg",
      "link": "https://example.com",
      "column": 1,
      "order": 1
    }
  ],
  "user": {
    "name": "Owner Name"
  }
}
```

### 5. Get Mosaic by ID
**GET** `/api/public/mosaics/{id}`

Retrieve a mosaic by its ID.

**Parameters:**
- `id` (integer) - The mosaic ID

## Nuxt.js Integration Examples

### 1. API Plugin Setup

Create `~/plugins/vams-api.js`:

```javascript
export default function ({ $axios }, inject) {
  // Create a custom axios instance for VAMS API
  const vamsApi = $axios.create({
    baseURL: 'https://your-vams-domain.com/api/public'
  })

  // Set default headers
  vamsApi.setHeader('Content-Type', 'application/json')
  vamsApi.setHeader('Accept', 'application/json')
  
  // Set API key (you can get this from environment variables)
  vamsApi.setHeader('X-API-Key', process.env.VAMS_API_KEY)

  // Inject to context as $vamsApi
  inject('vamsApi', vamsApi)
}
```

Register the plugin in `nuxt.config.js`:
```javascript
export default {
  plugins: [
    '~/plugins/vams-api.js'
  ],
  
  env: {
    VAMS_API_KEY: process.env.VAMS_API_KEY
  }
}
```

### 2. Environment Configuration

Create `.env` file:
```
VAMS_API_KEY=your-api-key-here
```

### 3. Using the API in Components

#### Test Connection
```vue
<template>
  <div>
    <button @click="testConnection">Test API Connection</button>
    <p v-if="connectionStatus">{{ connectionStatus }}</p>
  </div>
</template>

<script>
export default {
  data() {
    return {
      connectionStatus: null
    }
  },
  
  methods: {
    async testConnection() {
      try {
        const response = await this.$vamsApi.get('/test')
        this.connectionStatus = `Connected! User: ${response.data.user}`
      } catch (error) {
        this.connectionStatus = `Error: ${error.response?.data?.message || error.message}`
      }
    }
  }
}
</script>
```

#### Fetch Album
```vue
<template>
  <div>
    <div v-if="album">
      <h1>{{ album.title }}</h1>
      <p>{{ album.description }}</p>
      <div class="images">
        <img 
          v-for="image in album.images" 
          :key="image.id"
          :src="`https://your-vams-domain.com${image.path}`"
          :alt="image.alt_text"
        />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  async asyncData({ params, $vamsApi }) {
    try {
      const response = await $vamsApi.get(`/albums/by-title/${encodeURIComponent(params.title)}`)
      return {
        album: response.data
      }
    } catch (error) {
      console.error('Failed to fetch album:', error)
      return {
        album: null
      }
    }
  }
}
</script>
```

#### Fetch Mosaic
```vue
<template>
  <div>
    <div v-if="mosaic">
      <h1>{{ mosaic.title }}</h1>
      <p>{{ mosaic.description }}</p>
      <div class="mosaic-grid">
        <div 
          v-for="item in mosaic.items" 
          :key="item.id"
          class="mosaic-item"
          :class="`column-${item.column}`"
        >
          <img 
            v-if="item.image_path"
            :src="`https://your-vams-domain.com${item.image_path}`"
            :alt="item.title"
          />
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
          <a v-if="item.link" :href="item.link" target="_blank">View More</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  async asyncData({ params, $vamsApi }) {
    try {
      const response = await $vamsApi.get(`/mosaics/by-title/${encodeURIComponent(params.title)}`)
      return {
        mosaic: response.data
      }
    } catch (error) {
      console.error('Failed to fetch mosaic:', error)
      return {
        mosaic: null
      }
    }
  }
}
</script>

<style scoped>
.mosaic-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.mosaic-item {
  border: 1px solid #ddd;
  padding: 1rem;
  border-radius: 8px;
}

.mosaic-item img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 4px;
}
</style>
```

### 4. Nuxt Routes Example

Your Nuxt application could have these routes:

```
pages/
├── albums/
│   └── _title.vue          # /albums/my-album-title
├── mosaics/
│   └── _title.vue          # /mosaics/my-mosaic-title
└── test-api.vue            # /test-api
```

### 5. Error Handling

```javascript
// In your Nuxt plugin or composable
export const useVamsApi = () => {
  const handleApiError = (error) => {
    if (error.response?.status === 401) {
      return 'Invalid API key or unauthorized access'
    } else if (error.response?.status === 404) {
      return 'Content not found'
    } else if (error.response?.status === 429) {
      return 'Too many requests, please try again later'
    }
    return error.message || 'An error occurred'
  }

  const fetchAlbum = async (title) => {
    try {
      const response = await $nuxt.$vamsApi.get(`/albums/by-title/${encodeURIComponent(title)}`)
      return { data: response.data, error: null }
    } catch (error) {
      return { data: null, error: handleApiError(error) }
    }
  }

  const fetchMosaic = async (title) => {
    try {
      const response = await $nuxt.$vamsApi.get(`/mosaics/by-title/${encodeURIComponent(title)}`)
      return { data: response.data, error: null }
    } catch (error) {
      return { data: null, error: handleApiError(error) }
    }
  }

  return {
    fetchAlbum,
    fetchMosaic,
    handleApiError
  }
}
```

## Security Notes

1. **API Key Storage**: Store your API key in environment variables, never in your frontend code
2. **HTTPS Only**: Always use HTTPS in production
3. **Rate Limiting**: The API has rate limiting (60 requests per minute)
4. **User Scope**: API keys only return content belonging to the API key owner

## Getting Your API Key

1. Log into your VAMS admin panel
2. Go to Users management
3. Edit your user profile
4. Copy your API key from the API section
5. Add it to your Nuxt environment variables

## Testing

You can test your API integration using the built-in test page at:
`https://your-vams-domain.com/test-api` 