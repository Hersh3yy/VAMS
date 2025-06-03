<template>

    <Head title="Albums" />
    <AuthenticatedLayout>
        <template #header>
            <div class="header-container">
                <h2 class="header-title">Albums</h2>
                <Link :href="route('albums.create')" class="create-button">
                Create Album
                </Link>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="albums-container">
                <div class="albums-grid">
                    <div v-for="album in albums" :key="album.id" class="album-card">
                        <Link :href="route('albums.show', album.id)">
                        <div class="image-container">
                            <img 
                                v-if="album.cover_image_path && !imageErrors[album.id]"
                                :src="album.cover_image_path" 
                                :alt="album.title"
                                class="cover-image"
                                @error="handleImageError(album.id)"
                            >
                            <!-- Fallback placeholder -->
                            <div 
                                v-else
                                class="cover-image bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center"
                            >
                                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/>
                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                        <div class="album-details">
                            <h3 class="album-title">{{ album.title }}</h3>
                            <p class="album-description">{{ album.description || 'No description' }}</p>
                            <div class="text-sm text-gray-500 mt-2">
                                {{ album.images?.length || 0 }} {{ album.images?.length === 1 ? 'item' : 'items' }}
                            </div>
                        </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    albums: Array
});

const imageErrors = ref({});

const handleImageError = (albumId) => {
    imageErrors.value[albumId] = true;
};
</script>

<style scoped>
.header-container {
    @apply flex justify-between items-center;
}

.header-title {
    @apply font-semibold text-xl text-gray-800 leading-tight dark:text-yellow-200;
}

.create-button {
    @apply px-4 py-2 bg-yellow-600 text-black rounded-md hover:bg-yellow-500 font-medium;
}

.content-wrapper {
    @apply py-12;
}

.albums-container {
    @apply max-w-7xl mx-auto sm:px-6 lg:px-8;
}

.albums-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.album-card {
    @apply bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-900;
    @apply transition-all duration-300 ease-in-out;
}

.album-card:hover {
    @apply shadow-lg;
    box-shadow: 0 0 15px rgba(234, 179, 8, 0.5);
    border: 2px solid #eab308;
}

.image-container {
    @apply relative;
    aspect-ratio: 16/9;
}

.cover-image {
    @apply w-full h-full object-cover;
    /* This ensures all images maintain the same dimensions */
}

.album-details {
    @apply p-6;
}

.album-title {
    @apply text-lg font-semibold dark:text-yellow-200;
}

.album-description {
    @apply text-gray-600 mt-2 dark:text-yellow-400;
}
</style>