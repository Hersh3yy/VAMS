<template>
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4 text-center">
            Choose an Album
        </h4>
        <p class="text-sm text-gray-500 mb-8 text-center">
            Select an album to choose images from.
        </p>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto">
            <button
                v-for="album in albums"
                :key="album.id"
                @click="selectAlbum(album)"
                class="group relative bg-white border-2 border-gray-300 rounded-lg overflow-hidden hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                :class="{ 'border-blue-500 ring-2 ring-blue-500': selectedAlbum?.id === album.id }"
            >
                <div class="aspect-square bg-gray-100 flex items-center justify-center">
                    <img
                        v-if="album.cover_image_path"
                        :src="album.cover_image_path"
                        :alt="album.title"
                        class="w-full h-full object-cover"
                    />
                    <svg v-else class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" stroke-width="2"/>
                    </svg>
                </div>
                <div class="p-3">
                    <h5 class="text-sm font-medium text-gray-900 truncate">{{ album.title }}</h5>
                    <p class="text-xs text-gray-500 mt-1">{{ album.images?.length || 0 }} images</p>
                </div>
            </button>
        </div>

        <div class="mt-8 text-center" v-if="selectedAlbum">
            <button
                @click="confirm"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
                Continue with "{{ selectedAlbum.title }}"
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Album } from '@/types/album';

const props = defineProps<{
    albums: Album[];
}>();

const emit = defineEmits<{
    (e: 'select', album: Album): void;
}>();

const selectedAlbum = ref<Album | null>(null);

const selectAlbum = (album: Album) => {
    selectedAlbum.value = album;
};

const confirm = () => {
    if (selectedAlbum.value) {
        emit('select', selectedAlbum.value);
    }
};
</script> 