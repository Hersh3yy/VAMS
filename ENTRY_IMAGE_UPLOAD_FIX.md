# Entry Image Upload & Reordering Fix

## Problems Fixed

### 1. **Authentication Issue** ✅
**Problem:** The `/api/media/upload` endpoint requires Sanctum authentication, but the Inertia frontend was making axios requests without proper session credentials.

**Solution:** Configured axios in `bootstrap.ts` to:
- Send credentials with requests (`withCredentials: true`)
- Automatically include CSRF tokens in request headers
- Use request interceptors to ensure fresh CSRF tokens

**Files Modified:**
- `resources/js/bootstrap.ts`

### 2. **Drag-and-Drop Snap-back Issue** ✅
**Problem:** When reordering images via drag-and-drop, they would snap back to their original position before updating. This was caused by a deep watch on `form.content` that re-initialized Sortable on every change.

**Solution:** Created a dedicated `ImageCollectionManager` component that:
- Only re-initializes Sortable when the number of images changes (not on every edit)
- Properly manages Sortable instances with cleanup
- Handles local state updates immediately before sending to the server

**Files Created:**
- `resources/js/Components/atoms/ImageUploadZone.vue` - Reusable upload dropzone
- `resources/js/Components/molecules/ImageCollectionManager.vue` - Complete image collection manager with drag-and-drop
- `resources/js/Components/entries/EntryReadView.vue` - Read-only entry view component

**Files Modified:**
- `resources/js/Components/entries/DynamicEntryForm.vue` - Now uses `ImageCollectionManager`
- `resources/js/Components/entries/EntryModal.vue` - Simplified to use `EntryReadView`

### 3. **Atomic Design Refactoring** ✅
**Problem:** Large monolithic components with duplicated code.

**Solution:** Broke down components following atomic design principles:

#### Atoms Created:
- **ImageUploadZone.vue**: Reusable drag-and-drop upload zone
  - Configurable upload text and hints
  - Drag-and-drop support
  - File type filtering
  - Props: `multiple`, `accept`, `inputId`, `uploadText`, `hintText`

#### Molecules Created:
- **ImageCollectionManager.vue**: Complete image collection manager
  - Upload via click or drag-and-drop
  - Image preview grid
  - Drag-and-drop reordering
  - Alt text editing
  - Image removal
  - Props: `modelValue`, `label`, `required`, `maxImages`
  - Emits: `update:modelValue`

#### Organisms Simplified:
- **EntryReadView.vue**: Dedicated read-only view for entries
  - Handles all field types (text, textarea, repeatable, image_collection, object)
  - Clean presentation layer
  - Reusable across entry types

## Testing Instructions

### Prerequisites
1. Rebuild frontend assets:
   ```bash
   npm run build
   # OR for development with hot reload:
   npm run dev
   ```

2. Ensure you're logged in as an authenticated user

### Test 1: Image Upload (Click)
1. Navigate to an entry with an `image_collection` field (e.g., Recipe)
2. Click "Edit" on an entry (or create a new one)
3. Click the upload zone
4. Select one or more images
5. **Expected:** Images upload successfully and appear in the grid
6. **Verify:** No "unauthenticated" errors in the console

### Test 2: Image Upload (Drag & Drop)
1. While editing an entry
2. Drag image files from your file system
3. Drop them onto the upload zone
4. **Expected:** Images upload successfully
5. **Verify:** Upload progress indicator shows briefly
6. **Verify:** Images appear in the grid

### Test 3: Image Reordering
1. Upload at least 3 images to an entry
2. Drag an image to a new position
3. **Expected:** Image moves smoothly and STAYS in new position
4. **Previous Bug:** Image would snap back then update on refresh
5. **Now:** Image should stay in place immediately
6. Save the entry
7. Refresh the page
8. **Verify:** Order is preserved

### Test 4: Image Metadata
1. Upload images
2. Edit alt text in the text field below each image
3. **Expected:** Changes save with the entry
4. View the entry in read-only mode
5. **Verify:** Alt text is displayed

### Test 5: Image Removal
1. Upload images
2. Hover over an image
3. Click the red X button
4. **Expected:** Image is removed from the grid
5. Save the entry
6. **Verify:** Removed image is not in the saved entry

### Test 6: Read-Only View
1. Save an entry with images
2. Close the modal
3. Click to view the entry (not edit)
4. **Expected:** Images display in a clean grid
5. **Verify:** Alt text is shown below images
6. **Verify:** No edit controls are visible

## Technical Details

### Authentication Flow
```
Frontend (axios) → Include CSRF Token + Credentials
    ↓
Laravel Middleware (auth:sanctum)
    ↓
Session Cookie Validation
    ↓
MediaController@upload
    ↓
ImageService@storeImage
    ↓
S3/DigitalOcean Spaces
    ↓
Return URL & Path
```

### Sortable.js Integration
- **Initialization:** Only when component mounts or image count changes
- **onEnd Event:** Updates local array immediately
- **UI Update:** Happens instantly via Vue reactivity
- **No Re-initialization:** When editing alt text or other metadata

### Component Hierarchy
```
EntryModal
├── DynamicEntryForm (edit mode)
│   └── ImageCollectionManager (for image_collection fields)
│       └── ImageUploadZone (upload UI)
└── EntryReadView (read mode)
```

## Browser Console Debugging

If you encounter issues, check the browser console for:

### Success Indicators:
```javascript
// Successful upload
POST /api/media/upload 200 OK
{success: true, data: {url: "...", path: "..."}}
```

### Error Indicators:
```javascript
// Authentication error (should be fixed now)
POST /api/media/upload 401 Unauthorized

// CSRF token error (should be fixed now)  
POST /api/media/upload 419 CSRF Token Mismatch

// File validation error
POST /api/media/upload 422 Unprocessable Entity
```

## Files Summary

### Modified:
1. `resources/js/bootstrap.ts` - Axios configuration for session auth
2. `resources/js/Components/entries/DynamicEntryForm.vue` - Uses ImageCollectionManager
3. `resources/js/Components/entries/EntryModal.vue` - Uses EntryReadView

### Created:
1. `resources/js/Components/atoms/ImageUploadZone.vue` - Reusable upload zone
2. `resources/js/Components/molecules/ImageCollectionManager.vue` - Image collection manager
3. `resources/js/Components/entries/EntryReadView.vue` - Read-only entry view

### No Changes Required:
- Backend routes and controllers remain unchanged
- Database schema unchanged
- Existing tests remain valid

## Next Steps

After testing confirms everything works:

1. ✅ Image uploads work without authentication errors
2. ✅ Drag-and-drop reordering works smoothly
3. ✅ Alt text editing persists
4. ✅ Image removal works
5. ✅ Read-only view displays properly

Consider:
- Adding loading states with progress bars for large uploads
- Implementing image compression before upload
- Adding image preview modal on click
- Creating similar components for other media types (videos, documents)

## Rollback (if needed)

If issues occur, the changes are isolated:

1. `git checkout resources/js/bootstrap.ts` - Reverts auth changes
2. `git checkout resources/js/Components/entries/DynamicEntryForm.vue` - Reverts to old inline implementation
3. Delete new atomic components

The backend requires no changes to rollback.

