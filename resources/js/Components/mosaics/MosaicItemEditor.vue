<template>
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button
                        type="button"
                        class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="$emit('close')"
                    >
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ isEditing ? 'Edit Item' : 'Add Item' }}
                        </h3>

                        <div class="mt-4 space-y-4">
                            <!-- Content Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Content Type</label>
                                <select
                                    v-model="itemType"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                >
                                    <option value="album">Album</option>
                                    <option value="media">Media</option>
                                    <option value="color">Color</option>
                                </select>
                            </div>

                            <!-- Album Selection -->
                            <div v-if="itemType === 'album'">
                                <label class="block text-sm font-medium text-gray-700">Select Album</label>
                                <select
                                    v-model="properties.album_id"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                >
                                    <option v-for="album in albums" :key="album.id" :value="album.id">
                                        {{ album.title }}
                                    </option>
                                </select>
                            </div>

                            <!-- Media Upload -->
                            <div v-if="itemType === 'media'">
                                <label class="block text-sm font-medium text-gray-700">Upload Media</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>Upload a file</span>
                                                <input type="file" class="sr-only" @change="handleFileUpload" accept="image/*,video/*">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Color Selection -->
                            <div v-if="itemType === 'color'">
                                <label class="block text-sm font-medium text-gray-700">Background Color</label>
                                <input
                                    type="color"
                                    v-model="properties.color"
                                    class="mt-1 block w-full h-10 rounded-md"
                                >
                            </div>

                            <!-- Text Overlay -->
                            <div>
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="properties.show_text"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                    <label class="ml-2 block text-sm text-gray-900">Show Text Overlay</label>
                                </div>
                                <div v-if="properties.show_text" class="mt-2 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Title</label>
                                        <input
                                            type="text"
                                            v-model="properties.title"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Caption</label>
                                        <textarea
                                            v-model="properties.caption"
                                            rows="3"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Link -->
                            <div>
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="properties.has_link"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    >
                                    <label class="ml-2 block text-sm text-gray-900">Add Link</label>
                                </div>
                                <div v-if="properties.has_link" class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700">URL</label>
                                    <input
                                        type="url"
                                        v-model="properties.link_url"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="handleSave"
                    >
                        {{ isEditing ? 'Save Changes' : 'Add Item' }}
                    </button>
                    <button
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        v-if="isEditing"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="handleDelete"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { MosaicItem, MosaicItemProperties, Album } from '@/types/mosaic';

const props = defineProps<{
    item?: MosaicItem;
    albums: Album[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', item: Partial<MosaicItem>): void;
    (e: 'delete'): void;
}>();

const isEditing = computed(() => !!props.item);

const itemType = ref(props.item?.type || 'album');
const properties = ref<MosaicItemProperties>({
    album_id: props.item?.properties.album_id || null,
    media_url: props.item?.properties.media_url || null,
    color: props.item?.properties.color || '#ffffff',
    show_text: props.item?.properties.show_text || false,
    title: props.item?.properties.title || '',
    caption: props.item?.properties.caption || '',
    has_link: props.item?.properties.has_link || false,
    link_url: props.item?.properties.link_url || '',
});

const handleFileUpload = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('media', file);

    try {
        const response = await fetch(route('media.upload'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            throw new Error('Upload failed');
        }

        const data = await response.json();
        properties.value.media = {
            type: data.type,
            path: data.path,
        };
    } catch (error) {
        console.error('Upload failed:', error);
    }
};

const handleSave = () => {
    const itemData: Partial<MosaicItem> = {
        type: itemType.value,
        properties: {
            ...properties.value,
            // If it's an album type, ensure we have the album data
            album: itemType.value === 'album' ? props.albums.find(a => a.id === properties.value.album_id) : undefined,
        },
    };
    emit('save', itemData);
};

const handleDelete = () => {
    emit('delete');
};
</script> 