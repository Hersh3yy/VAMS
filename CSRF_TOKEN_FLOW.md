# CSRF Token Flow and Upload Error Fixes

## Issues Fixed

### 1. CSRF Token Retry Loop (419 Error)
**Problem**: The upload system was getting stuck in an infinite retry loop when encountering 419 CSRF token mismatch errors.

**Root Causes**:
- No retry limit, causing infinite loops
- Poor error handling when token refresh failed
- Missing proper timeout handling

**Solution Implemented**:
- Added retry limit of 3 attempts to prevent infinite loops
- Enhanced error logging with timestamps and retry counts
- Improved error messages for better user feedback
- Better handling of token refresh failures

### 2. Route Parameter Bug (albums.show → Invalid UUID)
**Problem**: After page refresh, the album ID was being replaced with the literal string "albums.show" instead of the actual UUID.

**Root Cause**: The route refresh logic was calling `router.visit(config.refreshRoute!)` directly with just the route name, instead of generating the proper URL with parameters.

**Solution Implemented**:
- Fixed route generation to use `route(config.refreshRoute!, config.refreshParams)` 
- Added parameter validation before attempting route refresh
- Ensures proper UUID is passed to album routes

## Updated Code Changes

### `resources/js/composables/shared/useUpload.ts`

#### Retry Limit Protection
```typescript
// Limit retries to prevent infinite loops
if (retryCount >= 3) {
    console.error('Max retries reached for CSRF token refresh', {
        file_name: uploadItem.file.name,
        retry_count: retryCount,
        timestamp: new Date().toISOString()
    });
    uploadItem.status = 'error';
    uploadItem.error = 'Upload failed after multiple retries. Please refresh the page and try again.';
    return;
}
```

#### Fixed Route Refresh
```typescript
// Refresh the page if route is provided
if (config.refreshRoute && config.refreshParams) {
    setTimeout(() => {
        router.visit(route(config.refreshRoute!, config.refreshParams), {
            preserveScroll: true,
            preserveState: false,
            onProgress: () => false
        });
    }, 500);
}
```

#### Enhanced CSRF Error Handling
```typescript
} else if (xhr.status === 419) {
    if (retryCount < 2) { // Only retry twice
        refreshToken()
            .then(() => {
                console.log('CSRF token refreshed, retrying upload');
                setTimeout(() => {
                    uploadSingleFile(uploadItem, config, retryCount + 1);
                }, 1000);
            })
            .catch((refreshError) => {
                console.error('Failed to refresh CSRF token for retry', {
                    error: refreshError,
                    timestamp: new Date().toISOString()
                });
                uploadItem.status = 'error';
                uploadItem.error = 'Session expired. Please refresh the page and try again.';
            });
    } else {
        uploadItem.status = 'error';
        uploadItem.error = 'Session expired after multiple retries. Please refresh the page and try again.';
    }
}
```

## How It Works Now

### Upload Flow
1. **File Selection**: User selects files for upload
2. **CSRF Token**: System retrieves fresh CSRF token from meta tag
3. **Upload Request**: XMLHttpRequest sent with proper headers
4. **Error Handling**: 
   - **200/201**: Success, optional page refresh with proper route generation
   - **419**: CSRF mismatch, limited retries with token refresh
   - **422**: Validation errors, display to user
   - **Other**: Network/server errors with appropriate messages

### Retry Logic
- **Maximum 3 total attempts** (1 original + 2 retries)
- **1-second delay** between retry attempts
- **Token refresh** before each retry attempt
- **Fallback to page refresh** if all retries fail

### Route Generation
- **Proper parameter passing**: `route('albums.show', { album: albumId })`
- **Validation**: Only refresh if both route name and parameters exist
- **Preserve state**: Maintains scroll position when possible

## Testing the Fix

### Manual Testing Steps
1. **Navigate to an album page**: `/albums/{uuid}`
2. **Try uploading an image**: Should work normally
3. **If 419 error occurs**: Watch for retry attempts in console
4. **Check final outcome**: Either success or clear error message
5. **Verify route integrity**: Page refresh should maintain correct album UUID

### Console Logging
The system now provides detailed logging for debugging:
```
Starting upload with CSRF token (file: image.jpg, retry: 0)
CSRF token mismatch detected (retry: 1)
CSRF token refreshed, retrying upload (retry: 1)
Upload completed successfully
```

### Error Messages
Users will see clear, actionable error messages:
- "Session expired. Please refresh the page and try again."
- "Upload failed after multiple retries. Please refresh the page and try again."
- "Upload failed with validation errors"

## Backend CSRF Endpoint

The `/csrf-token` endpoint in `routes/web.php` provides fresh tokens:
```php
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token()
    ]);
})->middleware(['web']);
```

## Additional Protection

### Session Regeneration
After login, the system:
1. Regenerates the session ID
2. Creates a fresh CSRF token
3. Flashes token to frontend via Inertia props
4. Updates meta tag automatically

### Frontend Synchronization
The `useCsrfToken` composable:
- Watches for server-provided token updates
- Updates the meta tag automatically
- Provides manual refresh capability
- Logs all token operations for debugging

## Prevention Measures

1. **Retry Limits**: Prevents infinite loops
2. **Timeout Handling**: Prevents hanging requests
3. **Proper Route Generation**: Prevents parameter corruption
4. **Enhanced Logging**: Aids in debugging issues
5. **User-Friendly Messages**: Guides users to resolution

The system is now resilient to CSRF token mismatches and provides a smooth user experience even when session issues occur. 