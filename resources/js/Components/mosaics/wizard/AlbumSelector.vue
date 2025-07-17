<template>
    <div>
        <h4 class="mb-4 text-center text-lg font-medium text-gray-900">Choose an Album</h4>
        <p class="mb-8 text-center text-sm text-gray-500">Select an album to choose images from.</p>

        <div class="grid max-h-96 grid-cols-2 gap-4 overflow-y-auto md:grid-cols-3 lg:grid-cols-4">
            <button
                v-for="album in albums"
                :key="album.id"
                @click="selectAlbum(album)"
                class="group relative overflow-hidden rounded-lg border-2 border-gray-300 bg-white transition-all duration-200 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{
                    'border-blue-500 ring-2 ring-blue-500': selectedAlbum?.id === album.id
                }"
            >
                <div class="flex aspect-square items-center justify-center bg-gray-100">
                    <img
                        v-if="album.cover_image_path"
                        :src="album.cover_image_path"
                        :alt="album.title"
                        class="h-full w-full object-cover"
                    />
                    <svg
                        v-else
                        class="h-12 w-12 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2" />
                        <circle cx="8.5" cy="8.5" r="1.5" stroke-width="2" />
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" stroke-width="2" />
                    </svg>
                </div>
                <div class="p-3">
                    <h5 class="truncate text-sm font-medium text-gray-900">
                        {{ album.title }}
                    </h5>
                    <p class="mt-1 text-xs text-gray-500">{{ album.images?.length || 0 }} images</p>
                </div>
            </button>
        </div>

        <div class="mt-8 text-center" v-if="selectedAlbum">
            <button @click="confirm" class="btn-primary inline-flex items-center">
                Continue with "{{ selectedAlbum.title }}"
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Album } from '@/types/album';
import { ref } from 'vue';

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
