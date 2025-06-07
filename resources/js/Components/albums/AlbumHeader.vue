<template>
    <div class="flex justify-between items-center">
        <div class="flex items-center">
            <Link :href="route('albums.index')" 
                class="mr-4 btn-secondary rounded-full inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </Link>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ album.title }}</h2>
        </div>
        <div class="flex space-x-3">
            <button @click="handleDelete" class="btn-danger">
                Delete Album
            </button>
            <Link :href="route('albums.edit', album.id)" class="btn-primary">
                Edit Album
            </Link>
            <label class="btn-primary cursor-pointer">
                Add Images
                <input type="file" multiple @change="handleFileUpload" accept="image/*" class="hidden" />
            </label>
            <button @click="handleAddVideo" class="btn-primary flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Add Video
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { inject } from 'vue';
import type { Album } from '@/types/album';

const props = defineProps<{
    album: Album;
}>();

const emit = defineEmits<{
    (e: 'delete'): void;
    (e: 'upload', event: Event): void;
    (e: 'add-video'): void;
}>();

const showError = inject('showError', (message: string) => console.error(message));

const handleFileUpload = (event: Event) => {
    try {
        emit('upload', event);
    } catch (error) {
        showError('Failed to upload files. Please try again.');
    }
};

const handleDelete = () => {
    try {
        emit('delete');
    } catch (error) {
        showError('Failed to delete album. Please try again.');
    }
};

const handleAddVideo = () => {
    try {
        emit('add-video');
    } catch (error) {
        showError('Failed to open video modal. Please try again.');
    }
};
</script> 