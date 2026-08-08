<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <Link
                :href="route('albums.index')"
                class="btn-secondary mr-4 inline-flex items-center rounded-full"
            >
                <svg class="mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
                Back
            </Link>
            <h1 class="text-xl font-semibold leading-tight text-gray-800">
                {{ album.title }}
            </h1>
        </div>
        <HeaderActions
            entity-type="Album"
            :edit-url="route('albums.edit', album.id)"
            @delete="handleDelete"
            @upload="handleFileUpload"
            @add-video="handleAddVideo"
        />
    </div>
</template>

<script setup lang="ts">
import HeaderActions from '@/Components/organisms/HeaderActions.vue';
import type { Album } from '@/types/album';
import { Link } from '@inertiajs/vue3';
import { inject } from 'vue';

const _props = defineProps<{
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
