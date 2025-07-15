<template>
    <div class="relative bg-gray-900 text-white">
        <!-- Cover Image Background -->
        <div v-if="album.cover_image_path" class="absolute inset-0">
            <img
                :src="album.cover_image_path"
                :alt="album.title"
                class="h-full w-full object-cover opacity-50"
                @error="handleImageError"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-black/20 to-black/70" />
        </div>

        <!-- Content -->
        <div class="relative px-6 py-16 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col items-center space-y-4 text-center">
                    <!-- Album Title -->
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {{ album.title }}
                    </h1>

                    <!-- Album Description -->
                    <p v-if="album.description" class="max-w-3xl text-lg text-gray-200 sm:text-xl">
                        {{ album.description }}
                    </p>

                    <!-- Album Meta -->
                    <div
                        class="flex flex-wrap items-center justify-center gap-4 text-sm text-gray-300"
                    >
                        <span v-if="album.images?.length">
                            {{ album.images.length }}
                            {{ album.images.length === 1 ? 'item' : 'items' }}
                        </span>
                        <span v-if="album.images?.length">•</span>
                        <span> Created {{ formatDate(album.created_at) }} </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fallback gradient when no cover image -->
        <div
            v-if="!album.cover_image_path || imageError"
            class="absolute inset-0 -z-10 bg-gradient-to-br from-blue-600 to-purple-700"
        />
    </div>
</template>

<script setup lang="ts">
import type { Album } from '@/types/album';
import { ref } from 'vue';

const props = defineProps<{
    album: Album;
}>();

const imageError = ref(false);

const handleImageError = () => {
    imageError.value = true;
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>
