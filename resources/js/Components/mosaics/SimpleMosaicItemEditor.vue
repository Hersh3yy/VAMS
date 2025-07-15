<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click="handleBackdropClick"
    >
        <div
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-lg"
            @click.stop
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b p-4">
                <h3 class="text-lg font-medium">
                    {{ item?.id ? 'Edit Item' : 'Add Item' }}
                </h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div class="space-y-6 p-6">
                <!-- VIEW MODE: Show existing item with simple text editing -->
                <div v-if="isViewMode">
                    <!-- Current Image Display -->
                    <div class="space-y-4">
                        <label class="block text-sm font-medium text-gray-700">Current Image</label>
                        <div class="rounded-lg border bg-gray-50 p-4">
                            <div class="h-48 w-full overflow-hidden rounded">
                                <img
                                    :src="getDisplayImageSrc()"
                                    :alt="getDisplayImageAlt()"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="mt-3 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ getDisplayImageAlt() }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Selected Album:{{ selectedAlbum?.title }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Text Field -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Display Text
                        </label>
                        <input
                            v-model="editText"
                            type="text"
                            placeholder="Text to display on hover"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Switch to Edit Mode Button -->
                    <div class="flex justify-center pt-4">
                        <button
                            @click="toggleEditMode"
                            class="text-sm font-medium text-blue-600 hover:text-blue-800"
                        >
                            Change Image or Link → Full Edit Mode
                        </button>
                    </div>
                </div>

                <!-- EDIT MODE: Full interface for album/image selection -->
                <div v-else>
                    <!-- Album Selection -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Select Album
                        </label>

                        <!-- Visual Album Grid -->
                        <div
                            v-if="!selectedAlbumId"
                            class="grid max-h-64 grid-cols-2 gap-4 overflow-y-auto md:grid-cols-3"
                        >
                            <div
                                v-for="album in albums"
                                :key="album.id"
                                @click="selectedAlbumId = album.id.toString()"
                                class="group relative cursor-pointer overflow-hidden rounded-lg border-2 border-gray-200 transition-all duration-200 hover:border-blue-500"
                            >
                                <div class="aspect-square">
                                    <img
                                        :src="album.cover_image_path || '/placeholder.jpg'"
                                        :alt="album.title"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 transition-all duration-200 group-hover:bg-opacity-40"
                                    >
                                        <div
                                            class="text-center opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                                        >
                                            <h3 class="text-sm font-medium text-white">
                                                {{ album.title }}
                                            </h3>
                                            <p class="text-xs text-white">
                                                {{ album.images?.length || 0 }}
                                                images
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Album Display -->
                        <div v-if="selectedAlbumId" class="space-y-4">
                            <div
                                class="flex items-center justify-between rounded-lg bg-blue-50 p-3"
                            >
                                <div class="flex items-center space-x-3">
                                    <img
                                        :src="selectedAlbum?.cover_image_path || '/placeholder.jpg'"
                                        :alt="selectedAlbum?.title"
                                        class="h-12 w-12 rounded object-cover"
                                    />
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            {{ selectedAlbum?.title }}
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            {{ albumImages.length }} images
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="
                                        selectedAlbumId = '';
                                        selectedAlbum = null;
                                    "
                                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                >
                                    Change Album
                                </button>
                            </div>

                            <!-- Album Images Grid -->
                            <div v-if="albumImages.length > 0" class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700"
                                    >Select Image from Album ({{
                                        albumImages.length
                                    }}
                                    images)</label
                                >
                                <div
                                    class="scrollbar-visible grid max-h-48 grid-cols-3 gap-2 overflow-y-auto"
                                >
                                    <div
                                        v-for="image in albumImages"
                                        :key="image.id"
                                        @click="selectImage(image)"
                                        class="relative aspect-square cursor-pointer overflow-hidden rounded border-2 border-gray-200 transition-all duration-200 hover:border-blue-500"
                                        :class="{
                                            'border-blue-500 ring-2 ring-blue-200':
                                                selectedImageId === image.id
                                        }"
                                    >
                                        <img
                                            :src="getImageUrl(image)"
                                            :alt="image.title || `Image ${image.id}`"
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-if="selectedImageId === image.id"
                                            class="absolute inset-0 flex items-center justify-center bg-blue-500 bg-opacity-50"
                                        >
                                            <svg
                                                class="h-6 w-6 text-blue-600"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Link Options (only in edit mode) -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Link (Optional)
                        </label>
                        <input
                            v-model="linkUrl"
                            type="text"
                            placeholder="Enter URL, album title, or path starting with '/'"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Edit Text Field -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Display Text (Optional)
                        </label>
                        <input
                            v-model="editText"
                            type="text"
                            placeholder="Text to display on hover"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Switch to View Mode Button -->
                    <div class="flex justify-center pt-4" v-if="item?.id">
                        <button
                            @click="toggleEditMode"
                            class="text-sm font-medium text-gray-600 hover:text-gray-800"
                        >
                            ← Back to Simple View
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 border-t p-4">
                <button @click="$emit('close')" class="btn-secondary">Cancel</button>
                <button @click="handleSave" class="btn-primary" :disabled="!isValid">
                    {{ item?.id ? 'Update' : 'Add' }} Item
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Album, MosaicItem } from '@/types/mosaic';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    item: MosaicItem | null;
    albums: Album[];
    mosaicId?: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', item: MosaicItem): void;
    (e: 'delete', item: MosaicItem): void;
    (e: 'update'): void;
}>();

// State
const isEditMode = ref(false);
const selectedAlbumId = ref<string>('');
const selectedAlbum = ref<Album | null>(null);
const selectedImageId = ref<string>('');
const linkUrl = ref('');
const editText = ref('');

// Computed properties
const isViewMode = computed(() => !isEditMode.value && props.item?.id);
const isValid = computed(() => {
    if (isViewMode.value) return true; // View mode is always valid for updates
    return selectedAlbumId.value !== ''; // Edit mode requires album selection
});

// Filter album images to exclude videos
const albumImages = computed(() => {
    if (!selectedAlbum.value?.images) return [];

    return selectedAlbum.value.images.filter(image => {
        if (image.properties) {
            try {
                const props =
                    typeof image.properties === 'string'
                        ? JSON.parse(image.properties)
                        : image.properties;

                if (props.type === 'video') {
                    return false;
                }
            } catch (error) {
                // If properties can't be parsed, assume it's an image
            }
        }
        return true;
    });
});

// Methods
const toggleEditMode = () => {
    isEditMode.value = !isEditMode.value;
};

const handleBackdropClick = () => {
    emit('close');
};

const selectImage = (image: any) => {
    selectedImageId.value = image.id;
};

const getImageUrl = (image: any) => {
    if (image.properties) {
        const props =
            typeof image.properties === 'string' ? JSON.parse(image.properties) : image.properties;

        if (props.thumbnail_url) {
            return props.thumbnail_url;
        }
    }
    return image.path;
};

const getDisplayImageSrc = () => {
    if (props.item?.properties?.selected_image) {
        return getImageUrl(props.item.properties.selected_image);
    }
    return selectedAlbum.value?.cover_image_path || '/placeholder.jpg';
};

const getDisplayImageAlt = () => {
    if (props.item?.properties?.selected_image) {
        return (
            'Selected image:' +
            (props.item.properties.selected_image.title ||
                props.item.properties.selected_image.caption ||
                '')
        );
    }
    return selectedAlbum.value?.title || 'Album';
};

const handleSave = () => {
    if (!isValid.value) return;

    const item: MosaicItem = {
        id: props.item?.id || '',
        type: 'album',
        column_index: props.item?.column_index || 0,
        order: props.item?.order || 0,
        properties: {
            album: {
                id: selectedAlbum.value!.id.toString(),
                title: selectedAlbum.value!.title,
                cover_image_path: selectedAlbum.value!.cover_image_path
            }
        }
    };

    // If a specific image is selected, include it
    if (selectedImageId.value && selectedAlbum.value!.images) {
        const selectedImage = selectedAlbum.value!.images.find(
            img => img.id === selectedImageId.value
        );
        if (selectedImage) {
            item.properties!.selected_image = {
                id: selectedImage.id,
                path: selectedImage.path,
                title: selectedImage.title || null,
                caption: selectedImage.caption || null,
                properties: selectedImage.properties || null
            };
        }
    }

    // Add link (only available in edit mode)
    if (linkUrl.value.trim()) {
        (item.properties! as any).link = linkUrl.value.trim();
    }

    // Add edit text (available in both modes)
    if (editText.value.trim()) {
        (item.properties! as any).edit_text = editText.value.trim();
    }

    emit('save', item);

    // Emit update event to trigger parent reactivity
    emit('update');

    // Close the modal after saving
    emit('close');
};

// Watch for props changes
watch(
    () => props.item,
    newItem => {
        if (newItem) {
            // Set edit mode based on whether it's a new item or existing
            isEditMode.value = !newItem.id;

            if (newItem.type === 'album' && newItem.properties?.album) {
                selectedAlbumId.value = newItem.properties.album.id;
                if (newItem.properties?.selected_image?.id) {
                    selectedImageId.value = newItem.properties.selected_image.id;
                }
            }

            linkUrl.value = (newItem.properties as any)?.link || '';
            editText.value = (newItem.properties as any)?.edit_text || '';
        } else {
            // Reset form for new items
            isEditMode.value = true; // New items start in edit mode
            selectedAlbumId.value = '';
            selectedAlbum.value = null;
            selectedImageId.value = '';
            linkUrl.value = '';
            editText.value = '';
        }
    },
    { immediate: true }
);

// Watch for selectedAlbumId changes
watch(selectedAlbumId, (newAlbumId, oldAlbumId) => {
    if (newAlbumId) {
        selectedAlbum.value =
            props.albums.find(album => album.id.toString() === newAlbumId) || null;
        if (oldAlbumId && oldAlbumId !== newAlbumId) {
            selectedImageId.value = '';
        }
    } else {
        selectedAlbum.value = null;
        selectedImageId.value = '';
    }
});
</script>

<style scoped>
.btn-primary {
    @apply rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700;
}

.btn-secondary {
    @apply rounded-md bg-gray-200 px-4 py-2 font-medium text-gray-800 transition-colors hover:bg-gray-300;
}

.scrollbar-visible {
    scrollbar-width: auto;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.scrollbar-visible::-webkit-scrollbar {
    width: 8px;
}

.scrollbar-visible::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.scrollbar-visible::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.scrollbar-visible::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
