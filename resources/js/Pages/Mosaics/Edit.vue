<template>
    <Head :title="mosaic.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="header-container">
                <h2 class="header-title">
                    {{ mosaic.title }}
                </h2>
                <div class="action-buttons">
                    <button @click="saveMosaic" class="save-button" :class="{ 'has-changes': hasUnsavedChanges }">
                        <span v-if="hasUnsavedChanges">Save Changes</span>
                        <span v-else>Save Layout</span>
                    </button>
                    <button @click="openAlbumSelector" class="add-images-button">
                        Add Images
                    </button>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <!-- Orientation Tabs -->
            <div class="orientation-tabs">
                <button 
                    @click="currentOrientation = 'landscape'" 
                    class="tab-button" 
                    :class="{ 'active': currentOrientation === 'landscape' }">
                    Landscape
                </button>
                <button 
                    @click="currentOrientation = 'portrait'" 
                    class="tab-button" 
                    :class="{ 'active': currentOrientation === 'portrait' }">
                    Portrait
                </button>
            </div>
            
            <!-- Instructions -->
            <div v-if="adjustingSplit" class="instructions-bar">
                <div class="flex items-center justify-center bg-blue-100 p-2 rounded-lg text-blue-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <span>Drag the blue divider to adjust the split position</span>
                </div>
            </div>
            
            <!-- Help Instructions -->
            <div class="help-instructions bg-yellow-50 p-3 mb-4 rounded-lg border border-yellow-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-md font-medium text-yellow-700 mb-2">How to create your mosaic layout:</h3>
                    <div class="flex gap-2">
                        <button @click="testModal" class="px-2 py-1 bg-blue-500 text-white text-xs rounded">Test Modal</button>
                        <button @click="debugRoutes" class="px-2 py-1 bg-red-500 text-white text-xs rounded">Debug Routes</button>
                    </div>
                </div>
                <ol class="list-decimal ml-6 text-sm text-yellow-600">
                    <li class="mb-1">Start with a blank tile and click the <span class="font-medium">Split</span> button to divide it</li>
                    <li class="mb-1">Adjust the split position by dragging the blue divider line</li>
                    <li class="mb-1">Click the <span class="font-medium">Image</span> button on any tile to add an image from your albums</li>
                    <li class="mb-1">Drag the image inside its tile to adjust its position</li>
                    <li class="mb-1">All changes are saved when you click the <span class="font-medium">Save Changes</span> button</li>
                </ol>
            </div>
            
            <div class="editor-container">
                <div v-if="!hasItems" class="empty-state">
                    <div class="empty-content">
                        <svg xmlns="http://www.w3.org/2000/svg" class="empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="empty-title">Start Your Mosaic Layout</h3>
                        <p class="empty-text">Click the button below to create your first tile, then use the Split and Image buttons to build your layout.</p>
                        <button @click="createInitialLayout" class="empty-button">
                            Create Layout
                        </button>
                    </div>
                </div>
                
                <!-- Grid Layout Editor -->
                <div v-else class="editor-card">
                    <div class="editor-grid" :class="currentOrientation">
                        <div v-for="item in mosaicItems" 
                             :key="item.id"
                             class="grid-item"
                             :style="getItemStyle(item)">
                            
                            <!-- Image Content -->
                            <div v-if="item.type === 'image'" 
                                 class="image-container"
                                 @mousedown="startImageDrag($event, item)">
                                <img v-if="getImageSrc(item)" 
                                     :src="getImageSrc(item)" 
                                     :alt="item.title || 'Mosaic image'"
                                     class="item-image"
                                     :style="getImagePositionStyle(item)">
                                <!-- Image Controls -->
                                <div class="image-actions">
                                    <button @click="assignImage(item)" class="image-action-button edit-image-button" title="Change Image">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="image-action-icon" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    <button @click="removeItem(item)" class="image-action-button remove-image-button" title="Remove Image">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="image-action-icon" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="drag-hint">Drag to position image</div>
                            </div>
                            
                            <!-- Container Content -->
                            <div v-else-if="item.type === 'container'" class="container-content">
                                <!-- Container Actions -->
                                <div class="container-actions">
                                    <button v-if="!item.split_direction || item.split_direction === 'none'" 
                                            @click="splitItem(item)" 
                                            class="container-button split-button" 
                                            title="Split Tile">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="container-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                                        </svg>
                                        <span class="container-label">Split</span>
                                    </button>
                                    <button v-else
                                            @click="toggleSplitAdjustment(item)"
                                            class="container-button adjust-button"
                                            :class="{ 'active': adjustingSplit && itemToSplit && itemToSplit.id === item.id }"
                                            title="Adjust Split">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="container-icon" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z" />
                                        </svg>
                                        <span class="container-label">Adjust</span>
                                    </button>
                                    <button @click="assignImage(item)" class="container-button add-image-button" title="Add Image">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="container-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="container-label">Image</span>
                                    </button>
                                    <button @click="removeItem(item)" class="container-button remove-button" title="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="container-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span class="container-label">Remove</span>
                                    </button>
                                </div>
                                
                                <div class="container-placeholder">
                                    <div class="container-info">
                                        <svg v-if="item.split_direction === 'none' || !item.split_direction" xmlns="http://www.w3.org/2000/svg" class="container-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                                        </svg>
                                        <svg v-else-if="item.split_direction === 'horizontal'" xmlns="http://www.w3.org/2000/svg" class="container-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                        <svg v-else-if="item.split_direction === 'vertical'" xmlns="http://www.w3.org/2000/svg" class="container-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4v16M12 4v16M18 4v16" />
                                        </svg>
                                        <span v-if="item.split_direction === 'none' || !item.split_direction" class="text-blue-700 font-medium">
                                            Click "Split" to divide this tile
                                        </span>
                                        <span v-else>
                                            {{ item.split_direction === 'horizontal' ? 'Horizontal Split' : 'Vertical Split' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Empty State -->
                            <div v-else class="placeholder">
                                <span>Empty Tile</span>
                                <button @click="assignImage(item)" class="add-content-button">
                                    Add Content
                                </button>
                            </div>
                            
                            <!-- Split Handle (only visible during adjustment) -->
                            <div 
                                v-if="adjustingSplit && itemToSplit && item.id === itemToSplit.id"
                                class="split-handle"
                                :class="itemToSplit.split_direction || 'horizontal'"
                                :style="getSplitHandleStyle(itemToSplit)"
                                @mousedown.prevent="handleSplitMouseDown">
                                <div class="handle-indicator"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Album Selector Modal -->
        <Modal :show="showAlbumSelector" @close="closeAlbumSelector" maxWidth="4xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title text-white">Select Images from Albums</h3>
                    <button @click="closeAlbumSelector" class="modal-close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div v-if="loadingAlbums" class="loading-container">
                    <div class="loading-spinner"></div>
                    <p class="text-white">Loading albums...</p>
                </div>
                
                <div v-else-if="!props.albums || props.albums.length === 0" class="empty-albums">
                    <p class="text-white">No albums found. Create an album first to add images.</p>
                    <Link :href="route('albums.create')" class="create-album-btn">
                        Create Album
                    </Link>
                </div>
                
                <div v-else>
                    <!-- Image Upload Section -->
                    <div class="upload-section mb-4 p-4 bg-gray-700 rounded-lg">
                        <h4 class="text-white text-lg mb-2">Upload New Image</h4>
                        <div class="flex flex-col md:flex-row gap-4 items-start">
                            <div class="flex-grow">
                                <label class="block text-sm text-gray-300 mb-1">Select Album</label>
                                <select 
                                    v-model="selectedAlbumId" 
                                    class="w-full p-2 rounded border border-gray-500 bg-gray-800 text-white"
                                    :disabled="isUploading">
                                    <option value="">Choose an album</option>
                                    <option v-for="album in props.albums" :key="album.id" :value="album.id">
                                        {{ album.title }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex-grow">
                                <label class="block text-sm text-gray-300 mb-1">Choose Image (JPG, PNG)</label>
                                <input 
                                    type="file" 
                                    id="file-upload"
                                    @change="handleFileSelect" 
                                    accept="image/jpeg,image/png,image/gif"
                                    class="w-full p-2 text-white bg-gray-800 rounded border border-gray-500"
                                    :disabled="isUploading">
                            </div>
                            <div class="flex-shrink-0">
                                <button 
                                    @click="uploadImage" 
                                    class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
                                    :disabled="isUploading || !selectedFile || !selectedAlbumId">
                                    <span v-if="isUploading">Uploading... {{ uploadProgress }}%</span>
                                    <span v-else>Upload</span>
                                </button>
                            </div>
                        </div>
                        <div v-if="isUploading" class="mt-2 w-full bg-gray-600 rounded-full h-2.5">
                            <div class="bg-blue-500 h-2.5 rounded-full" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                    </div>
                    
                    <!-- Album Selection -->
                    <div class="album-selector">
                        <div class="album-list">
                            <div 
                                v-for="album in props.albums" 
                                :key="album.id" 
                                @click="selectAlbum(album)"
                                class="album-item" 
                                :class="{ 'selected': selectedAlbumId === album.id }">
                                <img :src="album.cover_image_path || '/placeholder.jpg'" :alt="album.title" class="album-thumb">
                                <div class="album-info">
                                    <h4 class="album-name text-white">{{ album.title }}</h4>
                                    <p class="album-count text-gray-300">{{ album.images_count || 0 }} images</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Album Images -->
                        <div class="album-images">
                            <div v-if="!selectedAlbumId" class="no-album-selected">
                                <p class="text-white">Select an album to view images</p>
                            </div>
                            <div v-else-if="loadingImages" class="loading-container">
                                <div class="loading-spinner"></div>
                                <p class="text-white">Loading images...</p>
                            </div>
                            <div v-else-if="filteredAlbumImages.length === 0" class="no-images">
                                <p class="text-white">No images in this album</p>
                            </div>
                            <div v-else class="image-grid">
                                <div 
                                    v-for="image in filteredAlbumImages" 
                                    :key="image.id" 
                                    class="image-item"
                                    @click="addImageToMosaic(image)">
                                    <img :src="image.path" :alt="image.title || 'Album image'" class="thumb-image">
                                    <div class="image-overlay">
                                        <button class="add-image-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button @click="closeAlbumSelector" class="cancel-button">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
        
        <!-- Split Direction Modal - Make it simpler and more direct -->
        <Modal :show="showSplitModal" @close="closeSplitModal" maxWidth="md">
            <div class="modal-content bg-gray-800 p-6 text-white">
                <h3 class="modal-title text-xl font-bold text-center mb-6">Choose Split Direction</h3>
                <div class="split-options">
                    <button @click="confirmSplit('horizontal')" class="split-option">
                        <div class="split-preview horizontal-split"></div>
                        <span class="text-white">Horizontal Split</span>
                    </button>
                    <button @click="confirmSplit('vertical')" class="split-option">
                        <div class="split-preview vertical-split"></div>
                        <span class="text-white">Vertical Split</span>
                    </button>
                </div>
                <div class="modal-footer mt-6 flex justify-center">
                    <button @click="closeSplitModal" class="cancel-button bg-gray-600 hover:bg-gray-500">
                        Cancel
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Test Modal for Direct Testing -->
        <Modal :show="showTestModal" @close="() => { showTestModal = false }" maxWidth="md">
            <div class="p-6 bg-white">
                <h3 class="text-lg font-medium text-gray-900">Test Modal</h3>
                <p class="mt-2 text-sm text-gray-500">This is a test modal to verify the component is working.</p>
                <div class="mt-4 flex justify-end">
                    <button @click="showTestModal = false" class="px-4 py-2 bg-blue-500 text-white rounded">
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';

// Log that we've imported the Modal component
console.log('Modal component imported:', Modal != null);

const props = defineProps({
    mosaic: Object,
    items: Array,
    albums: Array
});

// Initialize with empty arrays to avoid undefined errors
const mosaicItems = ref([]);
const albumImages = ref([]);
const selectedFile = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const currentOrientation = ref('landscape'); // Default to landscape
const splitRatio = ref(50); // Default split at 50%
const hasUnsavedChanges = ref(false); // Track if there are unsaved changes
const nextClientId = ref(1); // For generating temporary client IDs

// UI state
const showAlbumSelector = ref(false);
const loadingAlbums = ref(false);
const selectedAlbumId = ref(null);
const loadingImages = ref(false);
const showSplitModal = ref(false);
const itemToSplit = ref(null);
const adjustingSplit = ref(false);
const isDraggingImage = ref(false);
const draggedItem = ref(null);
const imageOffset = ref({ x: 0, y: 0 }); // For image positioning within tile

// Create computed property to safely check for items
const hasItems = computed(() => {
    return mosaicItems.value && mosaicItems.value.length > 0;
});

// Filter albums to only include images (no videos)
const filteredAlbumImages = computed(() => {
    return albumImages.value.filter(image => {
        // Check if it's an image by extension or MIME type
        const isImage = image.path && (
            image.path.toLowerCase().match(/\.(jpeg|jpg|png|gif|webp)$/) ||
            (image.mime_type && image.mime_type.startsWith('image/'))
        );
        return isImage;
    });
});

// Additional initialization on component mount to ensure arrays are properly set up
onMounted(() => {
    // Initialize arrays explicitly
    if (!Array.isArray(albumImages.value)) albumImages.value = [];
    
    // Initialize with props.items if available, otherwise empty array
    if (Array.isArray(props.items) && props.items.length > 0) {
        mosaicItems.value = [...props.items];
        console.log('Loaded existing mosaic items:', mosaicItems.value);
    } else {
        mosaicItems.value = [];
        // No need to create initial layout here, let the user click the button
    }
    
    console.log('Albums from props:', props.albums);
});

// Function to safely open the album selector
const openAlbumSelector = () => {
    console.log('Opening album selector');
    showAlbumSelector.value = true;
};

// Function to close the album selector
const closeAlbumSelector = () => {
    showAlbumSelector.value = false;
};

// Function to close the split modal
const closeSplitModal = () => {
    console.log('Closing split modal');
    showSplitModal.value = false;
    itemToSplit.value = null;
};

// Generate a temporary client ID for new items
const generateClientId = () => {
    const clientId = `temp-${nextClientId.value}`;
    nextClientId.value++;
    return clientId;
};

const createInitialLayout = () => {
    // Create a root container that fills the entire grid
    const rootItem = {
        id: generateClientId(), // Use a temporary client ID
        mosaic_id: props.mosaic.id,
        type: 'container', 
        split_direction: 'none',
        desktop_position: JSON.stringify({ x: 0, y: 0, width: 100, height: 100 }),
        order: 0,
        isNew: true // Mark as new so we know to save it later
    };
    
    console.log('Creating initial layout:', rootItem);
    mosaicItems.value.push(rootItem);
    hasUnsavedChanges.value = true;
    
    // Show a hint message
    setTimeout(() => {
        alert('Your mosaic layout is ready! Click the Split button to divide the tile and begin creating your layout.');
    }, 500);
};

const getItemStyle = (item) => {
    let position = { x: 0, y: 0, width: 100, height: 100 };
    
    if (item.desktop_position) {
        // If it's already an object, use it
        if (typeof item.desktop_position === 'object') {
            position = item.desktop_position;
        } 
        // If it's a JSON string, parse it
        else if (typeof item.desktop_position === 'string') {
            try {
                position = JSON.parse(item.desktop_position);
            } catch (e) {
                console.error('Error parsing position:', e);
            }
        }
    }
    
    return {
        left: `${position.x}%`,
        top: `${position.y}%`,
        width: `${position.width}%`,
        height: `${position.height}%`,
    };
};

const getImageSrc = (item) => {
    if (item && item.type === 'image' && item.reference_id) {
        try {
            if (item.properties) {
                let props;
                if (typeof item.properties === 'string') {
                    props = JSON.parse(item.properties);
                } else {
                    props = item.properties;
                }
                
                // Try to use WebP if available and supported by browser
                if (props.webp_path && supportsWebP()) {
                    return props.webp_path;
                }
                
                return props.path || null;
            }
        } catch (e) {
            console.error('Error parsing image properties:', e);
        }
        
        return null;
    }
    return null;
};

// Get image positioning styles (position within the tile)
const getImagePositionStyle = (item) => {
    try {
        if (item.properties) {
            let props;
            if (typeof item.properties === 'string') {
                props = JSON.parse(item.properties);
            } else {
                props = item.properties;
            }
            
            // If image position is defined, use it
            if (props.position) {
                return {
                    objectPosition: `${props.position.x || 50}% ${props.position.y || 50}%`
                };
            }
        }
    } catch (e) {
        console.error('Error parsing image position:', e);
    }
    
    // Default to center
    return { objectPosition: '50% 50%' };
};

const saveMosaic = async () => {
    try {
        // First save the mosaic metadata
        await router.put(route('mosaics.update', props.mosaic.id), {
            title: props.mosaic.title,
            description: props.mosaic.description,
            layout_settings: JSON.stringify({
                columns: 4,
                orientation: currentOrientation.value
            })
        });
        
        // Then save all new or modified items
        const itemsToSave = mosaicItems.value.filter(item => item.isNew || item.isModified);
        
        if (itemsToSave.length > 0) {
            console.log('Saving modified items:', itemsToSave);
            
            for (const item of itemsToSave) {
                // Create new item or update existing
                if (item.isNew) {
                    // Remove client-side flags
                    const { isNew, isModified, ...itemToSave } = item;
                    
                    // Remove temporary ID if it exists
                    if (itemToSave.id && itemToSave.id.startsWith('temp-')) {
                        delete itemToSave.id;
                    }
                    
                    const response = await axios.post(route('mosaic-items.store'), itemToSave);
                    
                    // Update the item with the server-generated ID
                    const index = mosaicItems.value.findIndex(i => i.id === item.id);
                    if (index !== -1) {
                        mosaicItems.value[index] = response.data;
                    }
                } else if (item.isModified) {
                    // Remove client-side flags
                    const { isNew, isModified, ...itemToSave } = item;
                    
                    await axios.put(route('mosaic-items.update', item.id), itemToSave);
                }
            }
        }
        
        // Show success message
        alert('Mosaic saved successfully!');
        hasUnsavedChanges.value = false;
        
        // Refresh the page to get the updated data
        window.location.reload();
    } catch (error) {
        console.error('Failed to save mosaic:', error);
        alert('There was an error saving the mosaic. Please try again.');
    }
};

const selectAlbum = (album) => {
    if (!album || !album.id) return;
    
    selectedAlbumId.value = album.id;
    
    // Check if album images are already in props
    if (props.albums) {
        const selectedAlbum = props.albums.find(a => a.id === album.id);
        if (selectedAlbum && selectedAlbum.images) {
            albumImages.value = selectedAlbum.images;
            loadingImages.value = false;
            return;
        }
    }
    
    // Otherwise load from API
    loadAlbumImages(album.id);
};

const loadAlbumImages = (albumId) => {
    if (!albumId) return;
    
    loadingImages.value = true;
    
    // Use an API request format for JSON
    axios.get(`/api/albums/${albumId}`, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('Album images response:', response);
        if (response.data && response.data.images) {
            albumImages.value = response.data.images;
        } else if (response.data && response.data.data) {
            // Try alternate response format
            albumImages.value = response.data.data;
        } else {
            // Fallback - trigger a direct fetch using the album data from props
            const album = props.albums.find(a => a.id === albumId);
            if (album && album.images) {
                albumImages.value = album.images;
            } else {
                albumImages.value = [];
            }
        }
        loadingImages.value = false;
    })
    .catch(error => {
        console.error('Failed to load album images:', error);
        
        // Fallback - try to get images from the album in props
        const album = props.albums.find(a => a.id === albumId);
        if (album && album.images) {
            albumImages.value = album.images;
        } else {
            albumImages.value = [];
        }
        
        loadingImages.value = false;
    });
};

const addImageToMosaic = (image) => {
    if (!image) return;
    
    console.log('Adding image to mosaic:', image);
    
    // Use the selected container if there is one, otherwise find an empty one
    let container = null;
    
    if (itemToSplit.value && (itemToSplit.value.type === 'container' || itemToSplit.value.type === 'image')) {
        console.log('Using selected container for image:', itemToSplit.value);
        container = itemToSplit.value;
    } else {
        container = findEmptyContainer();
    }
    
    if (container) {
        console.log('Using container for image:', container);
        
        // Create image properties object with support for both JPG and WebP
        const imageProperties = {
            path: image.path,
            webp_path: image.webp_path || image.path, // Use WebP if available, fallback to original
            url: image.url || null,
            webp_url: image.webp_url || null,
            title: image.title || '',
            alt_text: image.alt_text || '',
            caption: image.caption || '',
            position: { x: 50, y: 50 } // Default center positioning
        };
        
        // Find the index of the container
        const index = mosaicItems.value.findIndex(item => item.id === container.id);
        
        if (index !== -1) {
            // Update the container to be an image type
            mosaicItems.value[index] = {
                ...container,
                type: 'image',
                reference_id: image.id,
                properties: JSON.stringify(imageProperties),
                isModified: true
            };
            
            // Force a reactive update to the array
            mosaicItems.value = [...mosaicItems.value];
            hasUnsavedChanges.value = true;
        }
        
        // Reset selected item
        itemToSplit.value = null;
        
        // Close the modal
        closeAlbumSelector();
    } else {
        // No empty container found, notify the user
        alert('Please split a container first to add more images');
    }
};

const findEmptyContainer = () => {
    console.log('Finding empty container from:', mosaicItems.value);
    
    // First try to find a container with no split direction
    let emptyContainer = mosaicItems.value.find(item => 
        item.type === 'container' && 
        (!item.split_direction || item.split_direction === 'none')
    );
    
    // If not found, try to find any container that doesn't have children
    if (!emptyContainer) {
        // Get all parent IDs
        const parentIds = mosaicItems.value
            .filter(item => item.parent_id)
            .map(item => item.parent_id);
        
        // Find containers that are not parents
        const childlessContainers = mosaicItems.value.filter(item => 
            item.type === 'container' && 
            !parentIds.includes(item.id)
        );
        
        console.log('Childless containers:', childlessContainers);
        
        if (childlessContainers.length > 0) {
            emptyContainer = childlessContainers[0];
        }
    }
    
    // If still not found, just use any container
    if (!emptyContainer) {
        emptyContainer = mosaicItems.value.find(item => item.type === 'container');
    }
    
    console.log('Found empty container:', emptyContainer);
    return emptyContainer;
};

const splitItem = (item) => {
    if (!item) {
        console.error('No item provided to split');
        return;
    }
    
    console.log('Attempting to split item:', item);
    
    if (item.type !== 'container') {
        alert('Only container tiles can be split');
        return;
    }
    
    // Calculate the aspect ratio of the tile to determine if we should
    // show both split options or automatically choose one
    const position = getPositionObject(item);
    const width = position.width;
    const height = position.height;
    const aspectRatio = width / height;
    
    console.log('Item dimensions:', { width, height, aspectRatio });
    
    itemToSplit.value = item;
    
    // If the aspect ratio is between 0.75 and 1.33 (close to square),
    // show the modal to choose direction. Otherwise, auto-select.
    if (aspectRatio >= 0.75 && aspectRatio <= 1.33) {
        console.log('Showing split modal for square-ish tile');
        // Force reset to ensure Vue catches the change
        showSplitModal.value = false;
        setTimeout(() => {
            showSplitModal.value = true;
            console.log('Modal visibility state:', showSplitModal.value);
        }, 50);
    } else if (aspectRatio < 0.75) {
        // Tall rectangle - split horizontally
        console.log('Auto-selecting horizontal split for tall tile');
        confirmSplit('horizontal');
    } else {
        // Wide rectangle - split vertically
        console.log('Auto-selecting vertical split for wide tile');
        confirmSplit('vertical');
    }
};

const getPositionObject = (item) => {
    let position = { x: 0, y: 0, width: 100, height: 100 };
    
    if (item.desktop_position) {
        // If it's already an object, use it
        if (typeof item.desktop_position === 'object') {
            position = item.desktop_position;
        } 
        // If it's a JSON string, parse it
        else if (typeof item.desktop_position === 'string') {
            try {
                position = JSON.parse(item.desktop_position);
            } catch (e) {
                console.error('Error parsing position:', e);
            }
        }
    }
    
    return position;
};

const confirmSplit = (direction) => {
    if (!direction || !itemToSplit.value) {
        console.error('Cannot split: direction or itemToSplit is missing', { direction, itemToSplit: itemToSplit.value });
        return;
    }
    
    console.log('Confirming split:', { direction, itemId: itemToSplit.value.id });
    showSplitModal.value = false;
    
    // Update the parent item to indicate it's split
    const parent = itemToSplit.value;
    const parentIndex = mosaicItems.value.findIndex(item => item.id === parent.id);
    
    if (parentIndex !== -1) {
        // Update the parent's split direction
        mosaicItems.value[parentIndex] = {
            ...parent,
            split_direction: direction,
            isModified: true
        };
        
        // Get parent position
        const parentPos = getPositionObject(parent);
        
        // Create two child containers
        const child1Position = { ...parentPos };
        const child2Position = { ...parentPos };
        
        if (direction === 'horizontal') {
            // Top and bottom halves
            child1Position.height = parentPos.height / 2;
            
            child2Position.y = parentPos.y + (parentPos.height / 2);
            child2Position.height = parentPos.height / 2;
        } else {
            // Left and right halves
            child1Position.width = parentPos.width / 2;
            
            child2Position.x = parentPos.x + (parentPos.width / 2);
            child2Position.width = parentPos.width / 2;
        }
        
        // Create the two child containers
        const child1 = {
            id: generateClientId(),
            mosaic_id: props.mosaic.id,
            parent_id: parent.id,
            type: 'container',
            split_direction: 'none',
            desktop_position: JSON.stringify(child1Position),
            order: 0,
            isNew: true
        };
        
        const child2 = {
            id: generateClientId(),
            mosaic_id: props.mosaic.id,
            parent_id: parent.id,
            type: 'container',
            split_direction: 'none',
            desktop_position: JSON.stringify(child2Position),
            order: 1,
            isNew: true
        };
        
        // Add the children to the items array
        mosaicItems.value.push(child1, child2);
        hasUnsavedChanges.value = true;
        
        // Show the split adjustment handles for this parent
        splitRatio.value = 50; // Reset to 50%
        setTimeout(() => {
            startSplitAdjustment(null, parent);
        }, 100);
    }
};

const updatePositionsAfterSplit = (parent, splitRatio) => {
    if (!parent) return;
    
    // Find the children of this parent
    const children = mosaicItems.value.filter(item => item.parent_id === parent.id);
    
    if (children.length !== 2) {
        console.error('Expected 2 children for split adjustment, found:', children.length);
        return;
    }
    
    // Get parent position
    const parentPos = getPositionObject(parent);
    const direction = parent.split_direction;
    
    if (direction === 'horizontal') {
        // Update top and bottom positions
        const topPosition = {
            ...parentPos,
            height: parentPos.height * (splitRatio / 100)
        };
        
        const bottomPosition = {
            ...parentPos,
            y: parentPos.y + parentPos.height * (splitRatio / 100),
            height: parentPos.height * (1 - splitRatio / 100)
        };
        
        // Update child positions
        updateItemPosition(children[0], topPosition);
        updateItemPosition(children[1], bottomPosition);
    } else {
        // Update left and right positions
        const leftPosition = {
            ...parentPos,
            width: parentPos.width * (splitRatio / 100)
        };
        
        const rightPosition = {
            ...parentPos,
            x: parentPos.x + parentPos.width * (splitRatio / 100),
            width: parentPos.width * (1 - splitRatio / 100)
        };
        
        // Update child positions
        updateItemPosition(children[0], leftPosition);
        updateItemPosition(children[1], rightPosition);
    }
};

const updateItemPosition = (item, newPosition) => {
    if (!item) return;
    
    // Find the item index
    const index = mosaicItems.value.findIndex(i => i.id === item.id);
    
    if (index !== -1) {
        // Update the position
        mosaicItems.value[index] = {
            ...mosaicItems.value[index],
            desktop_position: JSON.stringify(newPosition),
            isModified: true
        };
        
        hasUnsavedChanges.value = true;
    }
};

const removeItem = (item) => {
    if (!item) return;
    
    if (confirm('Are you sure you want to remove this item?')) {
        // Check if this is a parent item with children
        const children = mosaicItems.value.filter(i => i.parent_id === item.id);
        
        // Remove all children first if any
        if (children.length > 0) {
            children.forEach(child => {
                const childIndex = mosaicItems.value.findIndex(i => i.id === child.id);
                if (childIndex !== -1) {
                    mosaicItems.value.splice(childIndex, 1);
                }
            });
        }
        
        // Remove the item itself
        const index = mosaicItems.value.findIndex(i => i.id === item.id);
        if (index !== -1) {
            mosaicItems.value.splice(index, 1);
            hasUnsavedChanges.value = true;
        }
    }
};

const assignImage = (item) => {
    if (!item) return;
    
    console.log('Assigning image to item:', item);
    
    if (item.type !== 'container' && item.type !== 'image') {
        alert('Only empty containers or existing images can have images assigned');
        return;
    }
    
    // Store the item we want to assign an image to
    itemToSplit.value = item;
    
    // Open the album selector
    openAlbumSelector();
};

// Start dragging an image
const startImageDrag = (e, item) => {
    if (item.type !== 'image') return;
    
    // Prevent text selection
    e.preventDefault();
    
    // Mark this item as being dragged
    isDraggingImage.value = true;
    draggedItem.value = item;
    
    // Get the initial click position
    const startX = e.clientX;
    const startY = e.clientY;
    
    // Get current image position
    let position = { x: 50, y: 50 }; // Default center
    
    try {
        if (item.properties) {
            const props = typeof item.properties === 'string' 
                ? JSON.parse(item.properties) 
                : item.properties;
            
            if (props.position) {
                position = props.position;
            }
        }
    } catch (e) {
        console.error('Error parsing image position:', e);
    }
    
    // Store the starting offset
    imageOffset.value = position;
    
    // Add mousemove and mouseup listeners
    document.addEventListener('mousemove', handleImageDrag);
    document.addEventListener('mouseup', endImageDrag);
    
    console.log('Started image drag for:', draggedItem.value);
};

// Handle image dragging
const handleImageDrag = (e) => {
    if (!isDraggingImage.value || !draggedItem.value) return;
    
    // Get the image element dimensions
    const item = draggedItem.value;
    const index = mosaicItems.value.findIndex(i => i.id === item.id);
    if (index === -1) return;
    
    // Calculate new position (0-100 range)
    // We're adjusting the object-position CSS property which uses percentages
    // where 0% = show the left/top edge, 100% = show the right/bottom edge
    const offsetX = e.movementX / 5; // Adjust speed
    const offsetY = e.movementY / 5;
    
    // Update the image offset
    imageOffset.value = {
        x: Math.max(0, Math.min(100, imageOffset.value.x - offsetX)),
        y: Math.max(0, Math.min(100, imageOffset.value.y - offsetY))
    };
    
    // Parse the properties
    let properties;
    try {
        properties = typeof item.properties === 'string' 
            ? JSON.parse(item.properties) 
            : { ...item.properties };
    } catch (e) {
        console.error('Error parsing properties:', e);
        properties = {};
    }
    
    // Update the position in properties
    properties.position = imageOffset.value;
    
    // Update the item
    mosaicItems.value[index] = {
        ...item,
        properties: JSON.stringify(properties),
        isModified: true
    };
    
    hasUnsavedChanges.value = true;
};

// End image dragging
const endImageDrag = () => {
    isDraggingImage.value = false;
    draggedItem.value = null;
    
    document.removeEventListener('mousemove', handleImageDrag);
    document.removeEventListener('mouseup', endImageDrag);
};

// Add function to handle file selection
const handleFileSelect = (event) => {
    selectedFile.value = event.target.files[0];
    console.log('File selected:', selectedFile.value);
};

// Add function to upload image
const uploadImage = async () => {
    if (!selectedFile.value) {
        console.error('No file selected');
        return;
    }
    
    if (!selectedAlbumId.value) {
        alert('Please select an album first');
        return;
    }
    
    isUploading.value = true;
    uploadProgress.value = 0;
    
    const formData = new FormData();
    formData.append('image', selectedFile.value);
    formData.append('album_id', selectedAlbumId.value);
    formData.append('title', selectedFile.value.name);
    formData.append('description', '');
    formData.append('convert_to_webp', 'true');
    
    try {
        // Use the correct album-images store endpoint
        const response = await axios.post('/album-images', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json'
            },
            onUploadProgress: (progressEvent) => {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                uploadProgress.value = percentCompleted;
            }
        });
        
        console.log('Upload response:', response);
        
        if (response.data) {
            // Refresh album images
            loadAlbumImages(selectedAlbumId.value);
            
            // Clear the file input
            selectedFile.value = null;
            document.getElementById('file-upload').value = '';
        }
    } catch (error) {
        console.error('Upload failed:', error);
        alert('Failed to upload image. Please try again.');
    } finally {
        isUploading.value = false;
    }
};

// Check if browser supports WebP
const supportsWebP = () => {
    // Feature detection
    const canvas = document.createElement('canvas');
    if (canvas.getContext && canvas.getContext('2d')) {
        // Was able to create a canvas, now check WebP support
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }
    return false;
};

// Show split adjustment controls
const toggleSplitAdjustment = (item) => {
    if (!item || item.type !== 'container' || !item.split_direction || item.split_direction === 'none') {
        return;
    }
    
    // If already adjusting this item, turn off adjustment
    if (adjustingSplit.value && itemToSplit.value && itemToSplit.value.id === item.id) {
        adjustingSplit.value = false;
        itemToSplit.value = null;
        return;
    }
    
    // Otherwise start adjusting this item
    adjustingSplit.value = true;
    itemToSplit.value = item;
    
    // Get the current split ratio
    const children = mosaicItems.value.filter(i => i.parent_id === item.id);
    if (children.length === 2) {
        const child1Pos = getPositionObject(children[0]);
        const child2Pos = getPositionObject(children[1]);
        
        if (item.split_direction === 'horizontal') {
            // Calculate ratio based on height
            splitRatio.value = (child1Pos.height / (child1Pos.height + child2Pos.height)) * 100;
        } else {
            // Calculate ratio based on width
            splitRatio.value = (child1Pos.width / (child1Pos.width + child2Pos.width)) * 100;
        }
    } else {
        splitRatio.value = 50; // Default
    }
};

// Handle split adjustment
const startSplitAdjustment = (e, parent = null) => {
    // Set the item being split
    if (parent) {
        itemToSplit.value = parent;
    }
    
    adjustingSplit.value = true;
    
    // Prevent immediate click trigger
    e && e.stopPropagation();
    
    console.log('Started split adjustment for:', itemToSplit.value);
};

// Add an explicit mouse down handler for the split handle
const handleSplitMouseDown = (e) => {
    // Prevent any click events from triggering on parent elements
    e.stopPropagation();
    
    // Start tracking mouse movement for adjustment
    document.addEventListener('mousemove', handleSplitAdjustment);
    document.addEventListener('mouseup', endSplitAdjustment, { once: true });
    
    console.log('Split handle mousedown event triggered');
};

const handleSplitAdjustment = (e) => {
    if (!adjustingSplit.value || !itemToSplit.value) return;
    
    // Calculate the new split ratio based on mouse position
    const container = document.querySelector('.editor-grid');
    if (!container) return;
    
    const containerRect = container.getBoundingClientRect();
    const parentPos = getPositionObject(itemToSplit.value);
    
    // Calculate the actual pixel position of the parent container
    const parentLeft = containerRect.left + (containerRect.width * (parentPos.x / 100));
    const parentTop = containerRect.top + (containerRect.height * (parentPos.y / 100));
    const parentWidth = containerRect.width * (parentPos.width / 100);
    const parentHeight = containerRect.height * (parentPos.height / 100);
    
    if (itemToSplit.value.split_direction === 'horizontal') {
        // For horizontal split, use Y position relative to parent container
        const relativeY = e.clientY - parentTop;
        const percentY = (relativeY / parentHeight) * 100;
        splitRatio.value = Math.max(10, Math.min(90, percentY));
    } else {
        // For vertical split, use X position relative to parent container
        const relativeX = e.clientX - parentLeft;
        const percentX = (relativeX / parentWidth) * 100;
        splitRatio.value = Math.max(10, Math.min(90, percentX));
    }
    
    // Update the positions of the children
    updatePositionsAfterSplit(itemToSplit.value, splitRatio.value);
};

// Update endSplitAdjustment to ensure it properly applies the split
const endSplitAdjustment = (e) => {
    if (!adjustingSplit.value) return;
    
    document.removeEventListener('mousemove', handleSplitAdjustment);
    
    console.log('Ending split adjustment for:', itemToSplit.value);
    
    // Keep the item selected but turn off active adjustment
    // This allows the user to click the handle again to continue adjusting
    adjustingSplit.value = false;
};

// Updated split handle style to make it more visible
const getSplitHandleStyle = (item) => {
    if (!item || !item.split_direction) return {};
    
    const position = getPositionObject(item);
    
    if (item.split_direction === 'horizontal') {
        // For horizontal split, position at the splitRatio% from the top
        return {
            top: `${splitRatio.value}%`,
            left: '0',
            width: '100%',
            height: '8px',
            transform: 'translateY(-50%)',
            backgroundColor: 'rgba(59, 130, 246, 0.5)'
        };
    } else {
        // For vertical split, position at the splitRatio% from the left
        return {
            top: '0',
            left: `${splitRatio.value}%`,
            width: '8px',
            height: '100%',
            transform: 'translateX(-50%)',
            backgroundColor: 'rgba(59, 130, 246, 0.5)'
        };
    }
};

// Add additional state for test modal
const showTestModal = ref(false);

// Update test modal function
const testModal = () => {
    console.log('Opening test modal');
    showTestModal.value = true;
};

// Add debug routes function
const debugRoutes = () => {
    console.log('Debugging routes...');
    
    // Log some important routes to help diagnose the issue
    const routes = {
        'mosaic-items.store': route('mosaic-items.store'),
        'mosaic-items.update (example)': route('mosaic-items.update', 'example-id'),
        'mosaic-items.destroy (example)': route('mosaic-items.destroy', 'example-id'),
        'mosaic-items.split (example)': route('mosaic-items.split', 'example-id'),
        'mosaics.update': route('mosaics.update', props.mosaic.id)
    };
    
    console.log('Available routes:', routes);
    
    // Test a simple GET request to check if backend is responsive
    axios.get('/api')
        .then(response => {
            console.log('API base response:', response);
        })
        .catch(error => {
            console.error('API base error:', error);
        });
        
    alert('Route debugging information has been logged to the console.');
};
</script>

<style scoped>
.header-container {
    @apply flex justify-between items-center;
}

.header-title {
    @apply font-semibold text-xl text-gray-800 leading-tight;
}

.action-buttons {
    @apply flex gap-4;
}

.save-button {
    @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors;
}

.save-button.has-changes {
    @apply bg-green-500 hover:bg-green-600 animate-pulse;
}

.add-images-button {
    @apply bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded;
}

.content-wrapper {
    @apply py-12;
}

.editor-container {
    @apply max-w-7xl mx-auto px-4 overflow-visible;
}

.empty-state {
    @apply bg-white p-12 rounded-lg shadow flex items-center justify-center;
    min-height: 300px;
}

.empty-content {
    @apply text-center;
}

.empty-icon {
    @apply h-24 w-24 mx-auto text-blue-400;
}

.empty-title {
    @apply mt-4 text-xl font-medium text-gray-900;
}

.empty-text {
    @apply mt-2 text-sm text-gray-500 max-w-md mx-auto;
}

.empty-button {
    @apply mt-5 px-6 py-3 bg-blue-500 text-white text-lg font-medium rounded-md hover:bg-blue-600 transition-colors;
}

.editor-card {
    @apply bg-white p-4 rounded-lg shadow overflow-visible;
}

.editor-grid {
    @apply relative border border-gray-300 rounded-lg overflow-visible;
    min-height: 300px;
    background-size: 20px 20px;
    background-image: linear-gradient(to right, #f0f0f0 1px, transparent 1px),
                      linear-gradient(to bottom, #f0f0f0 1px, transparent 1px);
}

.editor-grid.landscape {
    aspect-ratio: 16/9;
    max-width: 100%;
    max-height: 70vh;
}

.editor-grid.portrait {
    aspect-ratio: 9/16;
    max-height: 70vh;
    width: 50%;
    min-width: 280px;
    max-width: 500px;
    margin: 0 auto;
}

/* For smaller screens, adjust the portrait mode */
@media (max-width: 768px) {
    .editor-grid.portrait {
        width: 90%;
        max-height: 60vh;
    }
}

.grid-item {
    @apply absolute overflow-hidden border border-gray-300 bg-gray-50;
}

/* Image Container Styles */
.image-container {
    @apply relative w-full h-full overflow-hidden;
    cursor: move; /* Show move cursor */
}

.item-image {
    @apply w-full h-full object-cover;
    transition: filter 0.2s;
}

.image-container:hover .item-image {
    filter: brightness(0.9);
}

.image-actions {
    @apply absolute top-2 right-2 flex flex-col z-30 gap-2 opacity-0 transition-opacity;
}

.image-container:hover .image-actions {
    @apply opacity-100;
}

.image-action-button {
    @apply bg-black bg-opacity-70 hover:bg-opacity-90 p-2 rounded-full flex items-center justify-center;
    width: 36px;
    height: 36px;
}

.image-action-icon {
    @apply text-white h-5 w-5;
}

.edit-image-button {
    @apply text-blue-400;
}

.remove-image-button {
    @apply text-red-400;
}

.drag-hint {
    @apply absolute inset-0 flex items-center justify-center text-white bg-black bg-opacity-50 opacity-0 transition-opacity text-sm font-medium;
}

.image-container:hover .drag-hint {
    @apply opacity-100;
}

/* Container Content Styles */
.container-content {
    @apply relative w-full h-full flex items-center justify-center;
}

.container-actions {
    @apply absolute top-2 left-2 flex flex-wrap z-20 gap-2 transition-opacity;
    max-width: calc(100% - 16px);
}

.container-placeholder {
    @apply w-full h-full flex items-center justify-center bg-blue-50 border-2 border-dashed border-blue-300;
}

.container-info {
    @apply flex flex-col items-center justify-center gap-1 p-2 rounded-lg bg-white bg-opacity-80 text-center;
    max-width: 80%;
}

.container-icon {
    @apply h-6 w-6 text-blue-600;
}

.container-button {
    @apply shadow-sm text-white p-2 rounded flex items-center gap-1 text-xs;
}

.container-label {
    @apply hidden sm:inline;
}

.split-button {
    @apply bg-blue-600 hover:bg-blue-700;
}

.adjust-button {
    @apply bg-blue-600 hover:bg-blue-700;
}

.adjust-button.active {
    @apply bg-blue-800 ring-2 ring-blue-500 ring-offset-1;
}

.add-image-button {
    @apply bg-green-600 hover:bg-green-700;
}

.remove-button {
    @apply bg-red-600 hover:bg-red-700;
}

.placeholder {
    @apply w-full h-full flex flex-col items-center justify-center text-gray-500;
}

.add-content-button {
    @apply mt-2 px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600;
}

.modal-content {
    @apply rounded-lg;
}

.modal-header {
    @apply flex justify-between items-center mb-4;
}

.modal-title {
    @apply text-lg font-medium text-gray-900;
}

.modal-close {
    @apply text-gray-400 hover:text-gray-500;
}

.modal-footer {
    @apply mt-6 flex justify-end;
}

.loading-container {
    @apply flex flex-col items-center justify-center py-12;
}

.loading-spinner {
    @apply w-12 h-12 border-4 border-blue-200 rounded-full border-t-blue-600 animate-spin mb-4;
}

.empty-albums {
    @apply text-center py-12;
}

.create-album-btn {
    @apply mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600;
}

.album-selector {
    @apply grid grid-cols-1 md:grid-cols-3 gap-6;
}

.album-list {
    @apply border-r border-gray-200 pr-4 md:col-span-1;
    max-height: 500px;
    overflow-y: auto;
}

.album-item {
    @apply flex items-center p-2 rounded cursor-pointer hover:bg-gray-100 mb-2;
}

.album-item.selected {
    @apply bg-blue-50 border border-blue-200;
}

.album-thumb {
    @apply w-16 h-16 object-cover rounded mr-3;
}

.album-info {
    @apply flex-1;
}

.album-name {
    @apply font-medium;
}

.album-count {
    @apply text-sm text-gray-500;
}

.album-images {
    @apply md:col-span-2;
    max-height: 500px;
    overflow-y: auto;
}

.no-album-selected, .no-images {
    @apply flex items-center justify-center h-full text-gray-500 py-12;
}

.image-grid {
    @apply grid grid-cols-2 sm:grid-cols-3 gap-4;
}

.image-item {
    @apply relative rounded overflow-hidden cursor-pointer;
    aspect-ratio: 1/1;
}

.thumb-image {
    @apply w-full h-full object-cover;
}

.image-overlay {
    @apply absolute inset-0 bg-black bg-opacity-50 opacity-0 flex items-center justify-center transition-opacity;
}

.image-item:hover .image-overlay {
    @apply opacity-100;
}

.add-image-btn {
    @apply bg-white rounded-full p-1 text-blue-600 hover:text-blue-800;
}

.cancel-button {
    @apply px-6 py-3 text-white rounded text-lg transition-colors;
}

.split-options {
    @apply grid grid-cols-2 gap-6 mt-6;
}

.split-option {
    @apply p-6 border border-gray-600 rounded-lg flex flex-col items-center hover:bg-gray-700 transition-colors;
}

.split-preview {
    @apply w-32 h-32 border border-gray-400 mb-4 rounded;
}

.horizontal-split {
    @apply relative;
    background: linear-gradient(to bottom, #3b82f6 50%, #60a5fa 50%);
}

.vertical-split {
    @apply relative;
    background: linear-gradient(to right, #3b82f6 50%, #60a5fa 50%);
}

.orientation-tabs {
    @apply flex justify-center mb-4;
}

.tab-button {
    @apply px-4 py-2 mx-2 rounded border border-gray-300;
}

.tab-button.active {
    @apply bg-blue-500 text-white border-blue-500;
}

.split-handle {
    @apply absolute z-20;
    cursor: pointer;
    background-color: rgba(59, 130, 246, 0.3);
    border: 2px solid rgba(59, 130, 246, 0.8);
    transition: background-color 0.2s;
}

.split-handle:hover {
    background-color: rgba(59, 130, 246, 0.6);
}

.split-handle.horizontal {
    @apply w-full h-6 -mt-3;
    cursor: ns-resize;
}

.split-handle.vertical {
    @apply h-full w-6 -ml-3;
    cursor: ew-resize;
}

.handle-indicator {
    @apply absolute bg-blue-500 rounded-full;
    width: 12px;
    height: 12px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
}

.action-button[title="Split Tile"] {
    @apply bg-blue-600 text-white;
}

.help-instructions {
    max-width: 800px;
    margin: 0 auto 1rem auto;
}

.instructions-bar {
    max-width: 800px;
    margin: 0 auto 1rem auto;
}
</style> 