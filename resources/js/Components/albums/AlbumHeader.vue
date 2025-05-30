<template>
    <div class="flex justify-between items-center">
        <div class="flex items-center">
            <Link :href="route('albums.index')" 
                class="mr-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-full inline-flex items-center transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </Link>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ album.title }}</h2>
        </div>
        <div class="flex space-x-3">
            <button @click="$emit('delete')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                Delete Album
            </button>
            <Link :href="route('albums.edit', album.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Edit Album
            </Link>
            <label class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer">
                Add Images
                <input type="file" class="hidden" multiple accept="image/*" @change="$emit('upload', $event)">
            </label>
            <button @click="$emit('add-video')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Add Video
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Album } from '@/types/album';

defineProps<{
    album: Album;
}>();

defineEmits<{
    (e: 'delete'): void;
    (e: 'upload', event: Event): void;
    (e: 'add-video'): void;
}>();
</script> 