<template>
    <Modal
        :modelValue="show"
        @update:modelValue="$emit('update:modelValue', $event)"
        @close="$emit('close')"
    >
        <div class="p-6">
            <h3 class="mb-4 text-lg font-medium text-gray-900">
                {{ isEditing ? 'Edit Item' : 'Add New Item' }}
            </h3>

            <!-- Content Type Selection -->
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium text-gray-700"
                    >What would you like to add?</label
                >
                <div class="grid grid-cols-3 gap-4">
                    <button
                        v-for="type in contentTypes"
                        :key="type.value"
                        @click="selectedType = type.value"
                        :class="[
                            'flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium',
                            selectedType === type.value
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <component :is="type.icon" class="mr-2 h-5 w-5" />
                        {{ type.label }}
                    </button>
                </div>
            </div>

            <!-- Album Selection -->
            <div v-if="selectedType === 'album'" class="space-y-4">
                <div v-if="!item.album_id" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700"
                        >Select Album</label
                    >
                    <div
                        v-if="albums.length === 0"
                        class="text-sm text-gray-500"
                    >
                        No albums available. Please create an album first.
                    </div>
                    <div v-else class="grid grid-cols-2 gap-4">
                        <div
                            v-for="album in albums"
                            :key="album.id"
                            class="relative aspect-square cursor-pointer overflow-hidden rounded-lg transition-all hover:ring-2 hover:ring-blue-500"
                            :class="{
                                'ring-2 ring-blue-500':
                                    selectedAlbum?.id === album.id,
                            }"
                            @click="selectAlbum(album)"
                        >
                            <img
                                :src="album.cover_image_path"
                                :alt="album.title"
                                class="h-full w-full object-cover"
                            />
                            <div
                                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40 opacity-0 transition-opacity hover:opacity-100"
                            >
                                <span class="text-sm font-medium text-white">{{
                                    album.title
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Selection -->
                <div v-if="selectedAlbum" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700"
                        >Select Image</label
                    >
                    <div class="grid grid-cols-3 gap-2">
                        <div
                            v-for="image in selectedAlbum.images"
                            :key="image.id"
                            class="relative aspect-square cursor-pointer overflow-hidden rounded-lg transition-all hover:ring-2 hover:ring-blue-500"
                            :class="{
                                'ring-2 ring-blue-500':
                                    selectedImage?.id === image.id,
                            }"
                            @click="selectImage(image)"
                        >
                            <img
                                :src="
                                    image.properties?.thumbnail_url ||
                                    image.path
                                "
                                :alt="image.title || 'Album image'"
                                class="h-full w-full object-cover"
                            />
                            <!-- Video badge -->
                            <div
                                v-if="image.properties?.type === 'video'"
                                class="absolute right-1 top-1 rounded-full bg-red-600 p-1 text-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3 w-3"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Properties (when video image is selected) -->
            <div
                v-if="
                    selectedType === 'album' &&
                    selectedImage?.properties?.type === 'video'
                "
                class="mt-6 space-y-4"
            >
                <h4 class="text-sm font-medium text-gray-700">
                    Video Properties
                </h4>
                <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Video URL</label
                        >
                        <input
                            type="url"
                            :value="
                                selectedImage.properties?.video_url ||
                                selectedImage.path
                            "
                            readonly
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                        />
                    </div>
                    <div v-if="selectedImage.properties?.thumbnail_url">
                        <label class="block text-sm font-medium text-gray-700"
                            >Thumbnail URL</label
                        >
                        <input
                            type="url"
                            :value="selectedImage.properties.thumbnail_url"
                            readonly
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                        />
                    </div>
                    <div class="text-sm text-gray-600">
                        <p>
                            <strong>Title:</strong>
                            {{ selectedImage.title || 'No title' }}
                        </p>
                        <p>
                            <strong>Caption:</strong>
                            {{ selectedImage.caption || 'No caption' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Media Upload -->
            <div v-if="selectedType === 'media'" class="space-y-4">
                <div
                    class="rounded-lg border-2 border-dashed border-gray-300 p-6 text-center"
                >
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleMediaUpload"
                        accept="image/*,video/*"
                        class="hidden"
                    />
                    <div v-if="!uploadedMedia" class="space-y-2">
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <button
                            @click="fileInput?.click()"
                            class="font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Click to upload image or video
                        </button>
                        <p class="text-sm text-gray-500">or drag and drop</p>
                    </div>
                    <div v-else class="relative">
                        <img
                            v-if="uploadedMedia.type === 'image'"
                            :src="uploadedMedia.preview"
                            class="h-48 w-full rounded-lg object-cover"
                        />
                        <video
                            v-else
                            :src="uploadedMedia.preview"
                            class="h-48 w-full rounded-lg object-cover"
                            controls
                        />
                        <button
                            @click="removeMedia"
                            class="absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Color Square -->
            <div v-if="selectedType === 'color'" class="space-y-4">
                <div class="grid grid-cols-6 gap-2">
                    <button
                        v-for="color in colors"
                        :key="color"
                        @click="selectedColor = color"
                        class="h-8 w-8 rounded-full border-2 transition-transform"
                        :class="[
                            selectedColor === color
                                ? 'scale-110 border-indigo-500'
                                : 'border-gray-300 hover:scale-105',
                        ]"
                        :style="{ backgroundColor: color }"
                    />
                </div>
            </div>

            <!-- Height Control -->
            <div class="mt-6 space-y-4">
                <label class="block text-sm font-medium text-gray-700"
                    >Item Height</label
                >
                <div class="flex items-center space-x-4">
                    <input
                        type="range"
                        v-model="itemHeight"
                        min="50"
                        max="500"
                        step="10"
                        class="w-full"
                        @input="emitLiveUpdate"
                    />
                    <span class="min-w-[60px] text-sm text-gray-500"
                        >{{ itemHeight }}%</span
                    >
                </div>
                <div class="text-xs text-gray-400">
                    Height as percentage of base tile size (200px = 100%)
                </div>
            </div>

            <!-- Image Manipulation Controls (only for images) -->
            <div
                v-if="
                    (selectedType === 'album' &&
                        selectedImage?.properties?.type !== 'video') ||
                    (selectedType === 'media' &&
                        uploadedMedia?.type === 'image')
                "
                class="mt-6 space-y-4"
            >
                <h4 class="text-sm font-medium text-gray-700">
                    Image Controls
                </h4>
                <div class="space-y-4 rounded-lg bg-gray-50 p-4">
                    <!-- Image Scale/Zoom -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700"
                            >Zoom</label
                        >
                        <div class="flex items-center space-x-4">
                            <input
                                type="range"
                                v-model="imageControls.scale"
                                min="100"
                                max="300"
                                step="5"
                                class="w-full"
                                @input="emitLiveUpdate"
                            />
                            <span class="min-w-[50px] text-sm text-gray-500"
                                >{{ imageControls.scale }}%</span
                            >
                        </div>
                    </div>

                    <!-- Image Position -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700"
                            >Position</label
                        >
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-600"
                                    >Horizontal (%)</label
                                >
                                <input
                                    type="range"
                                    v-model="imageControls.x"
                                    min="-50"
                                    max="50"
                                    step="1"
                                    class="w-full"
                                    @input="emitLiveUpdate"
                                />
                                <div class="text-center text-xs text-gray-500">
                                    {{ imageControls.x }}%
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600"
                                    >Vertical (%)</label
                                >
                                <input
                                    type="range"
                                    v-model="imageControls.y"
                                    min="-50"
                                    max="50"
                                    step="1"
                                    class="w-full"
                                    @input="emitLiveUpdate"
                                />
                                <div class="text-center text-xs text-gray-500">
                                    {{ imageControls.y }}%
                                </div>
                            </div>
                        </div>
                        <button
                            @click="resetImagePosition"
                            class="mt-2 text-xs text-blue-600 hover:text-blue-500"
                        >
                            Reset to center
                        </button>
                    </div>

                    <!-- Image Preview -->
                    <div class="mt-4">
                        <div
                            class="relative h-32 w-full overflow-hidden rounded-lg bg-gray-200"
                        >
                            <img
                                v-if="getImagePreviewUrl()"
                                :src="getImagePreviewUrl() || ''"
                                alt="Preview"
                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-200"
                                :style="{
                                    transform: `translate(${imageControls.x}%, ${imageControls.y}%) scale(${imageControls.scale / 100})`,
                                    transformOrigin: 'center center',
                                }"
                            />
                            <div
                                class="pointer-events-none absolute inset-0 rounded-lg border border-gray-300"
                            ></div>
                        </div>
                        <div class="mt-1 text-center text-xs text-gray-500">
                            Live preview of image positioning and zoom
                        </div>
                    </div>
                </div>
            </div>

            <!-- Text Overlay -->
            <div class="mt-6 space-y-4">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="hasText"
                        v-model="textOverlay.enabled"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label
                        for="hasText"
                        class="ml-2 block text-sm text-gray-900"
                    >
                        Add text overlay
                    </label>
                </div>

                <div v-if="textOverlay.enabled" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Text</label
                        >
                        <input
                            type="text"
                            v-model="textOverlay.text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Enter your text here..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Text Color</label
                        >
                        <select
                            v-model="textOverlay.color"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="white">White</option>
                            <option value="black">Black</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Link Option -->
            <div class="mt-6 space-y-4">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="hasLink"
                        v-model="linkOptions.enabled"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label
                        for="hasLink"
                        class="ml-2 block text-sm text-gray-900"
                    >
                        Make this item clickable
                    </label>
                </div>

                <div v-if="linkOptions.enabled" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Link URL</label
                        >
                        <input
                            type="url"
                            v-model="linkOptions.url"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="https://..."
                        />
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <button
                    v-if="isEditing"
                    @click="handleDelete"
                    class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-500"
                >
                    Delete
                </button>
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-500"
                >
                    Cancel
                </button>
                <button
                    @click="handleSave"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    {{ isEditing ? 'Save Changes' : 'Add Item' }}
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup lang="ts">
import Modal from '@/Components/general/Modal.vue';
import type { Album, AlbumImage } from '@/types/album';
import type { MosaicItem } from '@/types/mosaic';
import { computed, ref, watch } from 'vue';

interface MosaicItemWithImage extends MosaicItem {
    image_id?: string;
    path?: string;
}

const props = defineProps<{
    show: boolean;
    isEditing: boolean;
    item: MosaicItemWithImage;
    albums: Album[];
    mosaicId?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    close: [];
    save: [item: MosaicItemWithImage];
    delete: [itemId: string];
}>();

// State
const selectedType = ref<'album' | 'media' | 'color' | 'text' | 'video'>(
    props.item?.type || 'media',
);
const selectedAlbum = ref<Album | null>(
    props.item?.properties?.album
        ? {
              id: props.item.properties.album.id,
              title: props.item.properties.album.title,
              description: '',
              created_at: new Date().toISOString(),
              updated_at: new Date().toISOString(),
              cover_image_path:
                  props.item.properties.album.cover_image_path ?? undefined,
              images: props.item.properties.album.images,
          }
        : null,
);
const selectedImage = ref<AlbumImage | null>(null);
const uploadedMedia = ref<{
    type: 'image' | 'video';
    preview: string;
    serverData?: any;
} | null>(null);
const selectedColor = ref('#ffffff');
const itemHeight = ref(props.item?.properties?.height || 100);
const textOverlay = ref({
    enabled: props.item?.properties?.show_text || false,
    text: props.item?.properties?.text?.content || '',
    color: props.item?.properties?.text?.color || 'white',
});
const linkOptions = ref({
    enabled: props.item?.properties?.has_link || false,
    url: props.item?.properties?.link_url || '',
});
const imageControls = ref({
    scale: props.item?.properties?.media?.scale
        ? props.item.properties.media.scale * 100
        : 100,
    x: 0,
    y: 0,
});

// File input ref
const fileInput = ref<HTMLInputElement | null>(null);

// Content Types
const contentTypes = [
    { value: 'album' as const, label: 'Album', icon: 'AlbumIcon' },
    { value: 'media' as const, label: 'Media', icon: 'MediaIcon' },
    { value: 'color' as const, label: 'Color', icon: 'ColorIcon' },
    { value: 'text' as const, label: 'Text', icon: 'TextIcon' },
];

// Colors
const colors = [
    '#ffffff',
    '#000000',
    '#ff0000',
    '#00ff00',
    '#0000ff',
    '#ffff00',
    '#ff00ff',
    '#00ffff',
    '#808080',
    '#800000',
    '#008000',
    '#000080',
    '#808000',
    '#800080',
    '#008080',
];

// Live update emit
const emitLiveUpdate = () => {
    if (props.isEditing && props.item?.id) {
        const liveData = {
            id: props.item.id,
            height: itemHeight.value,
            imageControls: imageControls.value,
        };
        // Future: emit live update event to parent
    }
};

// Validation
const isValid = computed(() => {
    if (selectedType.value === 'album') {
        return selectedAlbum.value !== null && selectedImage.value !== null;
    }
    if (selectedType.value === 'media') {
        return uploadedMedia.value !== null;
    }
    if (selectedType.value === 'color') {
        return selectedColor.value !== null;
    }
    return false;
});

// Methods
const handleMediaUpload = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const file = input.files[0];
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');

    if (!isImage && !isVideo) {
        alert('Please upload an image or video file');
        return;
    }

    try {
        // Upload the file to the backend using the same approach as albums
        const formData = new FormData();
        formData.append('media', file);

        // Get CSRF token
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(
            route('mosaics.media.upload', { mosaic: props.mosaicId }),
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                },
                body: formData,
            },
        );

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Upload failed');
        }

        const result = await response.json();

        // Set the uploaded media with the server response
        uploadedMedia.value = {
            type: isImage ? 'image' : 'video',
            preview: result.data.path,
            serverData: result.data,
        };

        // Reset image controls for new media
        if (isImage) {
            resetImagePosition();
        }
    } catch (error) {
        console.error('Upload failed:', error);
        alert('Failed to upload file: ' + (error as Error).message);
    }
};

const removeMedia = () => {
    uploadedMedia.value = null;
    if (props.item?.id) {
        emit('delete', props.item.id);
    }
};

const getImagePreviewUrl = () => {
    if (selectedType.value === 'album' && selectedImage.value) {
        return (
            selectedImage.value.properties?.thumbnail_url ||
            selectedImage.value.path
        );
    }
    if (selectedType.value === 'media' && uploadedMedia.value) {
        return uploadedMedia.value.preview;
    }
    return undefined;
};

const resetImagePosition = () => {
    imageControls.value = {
        scale: 100,
        x: 0,
        y: 0,
    };
    emitLiveUpdate();
};

const handleSave = () => {
    const itemData: MosaicItemWithImage = {
        id: props.item?.id || '',
        type: selectedType.value,
        column_index: props.item?.column_index || 0,
        order: props.item?.order || 0,
        properties: {
            height: itemHeight.value,
        },
    };

    // Handle album selection
    if (selectedType.value === 'album' && selectedAlbum.value) {
        itemData.properties = {
            ...itemData.properties,
            album_id: selectedAlbum.value.id.toString(),
            album: {
                id: selectedAlbum.value.id.toString(),
                title: selectedAlbum.value.title,
                cover_image_path:
                    selectedAlbum.value.cover_image_path || undefined,
                images: selectedAlbum.value.images,
            },
        };

        // If an image is selected, use it instead of the album cover
        if (selectedImage.value) {
            itemData.properties = {
                ...itemData.properties,
                selected_image: {
                    id: selectedImage.value.id,
                    path: selectedImage.value.path,
                    title: selectedImage.value.title ?? undefined,
                    caption: selectedImage.value.caption ?? undefined,
                    properties: selectedImage.value.properties,
                },
            };
        }
    }

    // Handle media upload
    if (selectedType.value === 'media' && uploadedMedia.value) {
        itemData.properties = {
            ...itemData.properties,
            media: {
                type: uploadedMedia.value.type,
                path: uploadedMedia.value.preview,
                scale: imageControls.value.scale / 100,
                position: `${imageControls.value.x}% ${imageControls.value.y}%`,
                // Include server metadata
                mime_type: uploadedMedia.value.serverData?.mime_type,
                original_name: uploadedMedia.value.serverData?.original_name,
                size: uploadedMedia.value.serverData?.size,
                webp_url: uploadedMedia.value.serverData?.webp_url,
            },
        };
    }

    // Handle color selection
    if (selectedType.value === 'color') {
        itemData.properties = {
            ...itemData.properties,
            color: selectedColor.value,
        };
    }

    // Add text overlay if enabled
    if (textOverlay.value.enabled) {
        itemData.properties = {
            ...itemData.properties,
            show_text: true,
            text: {
                enabled: true,
                content: textOverlay.value.text,
                color: textOverlay.value.color,
            },
        };
    }

    // Add link if enabled
    if (linkOptions.value.enabled) {
        itemData.properties = {
            ...itemData.properties,
            has_link: true,
            link_url: linkOptions.value.url,
        };
    }

    emit('save', itemData);
    emit('update:modelValue', false);
};

const handleDelete = () => {
    if (props.item?.id) {
        emit('delete', props.item.id);
    }
};

const selectAlbum = (album: Album) => {
    selectedAlbum.value = album;
    // Clear image selection when changing albums
    if (props.item) {
        props.item.image_id = undefined;
        props.item.path = undefined;
    }
};

const selectImage = (image: AlbumImage) => {
    selectedImage.value = image;
    if (props.item) {
        props.item.image_id = image.id;
        props.item.path = image.path;
    }
    // Reset image controls for new image
    resetImagePosition();
};

// Watch for changes in props.item
watch(
    () => props.item,
    (newItem) => {
        if (newItem) {
            if (newItem.type) {
                selectedType.value = newItem.type;
            }
            selectedAlbum.value = newItem.properties?.album
                ? {
                      id: newItem.properties.album.id,
                      title: newItem.properties.album.title,
                      description: '',
                      created_at: new Date().toISOString(),
                      updated_at: new Date().toISOString(),
                      cover_image_path:
                          newItem.properties.album.cover_image_path ??
                          undefined,
                      images: newItem.properties.album.images,
                  }
                : null;
            selectedImage.value = null; // Reset selected image
            if (newItem.type === 'media' && newItem.properties?.media) {
                uploadedMedia.value = {
                    type: newItem.properties.media.type,
                    preview: newItem.properties.media.path,
                };
                // Load existing image controls
                imageControls.value = {
                    scale: newItem.properties.media.scale
                        ? newItem.properties.media.scale * 100
                        : 100,
                    x: 0, // Extract from position if needed
                    y: 0, // Extract from position if needed
                };
            } else {
                uploadedMedia.value = null;
            }
            selectedColor.value = newItem.properties?.color || '#ffffff';
            itemHeight.value = newItem.properties?.height || 100;
            textOverlay.value = {
                enabled: newItem.properties?.show_text || false,
                text: newItem.properties?.text?.content || '',
                color: newItem.properties?.text?.color || 'white',
            };
            linkOptions.value = {
                enabled: newItem.properties?.has_link || false,
                url: newItem.properties?.link_url || '',
            };
        }
    },
    { immediate: true },
);
</script>

<style scoped>
.drag-over {
    @apply border-indigo-500 bg-indigo-50;
}

input[type='range'] {
    @apply h-2 appearance-none rounded-lg bg-gray-200;
}

input[type='range']::-webkit-slider-thumb {
    @apply h-4 w-4 cursor-pointer appearance-none rounded-full bg-blue-600;
}

input[type='range']::-moz-range-thumb {
    @apply h-4 w-4 cursor-pointer appearance-none rounded-full border-0 bg-blue-600;
}
</style>
