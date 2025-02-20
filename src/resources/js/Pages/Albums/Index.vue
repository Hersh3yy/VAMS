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
                            <img :src="album.cover_image_path || '/placeholder.jpg'" :alt="album.title"
                                class="cover-image">
                        </div>
                        <div class="album-details">
                            <h3 class="album-title">{{ album.title }}</h3>
                            <p class="album-description">{{ album.description }}</p>
                        </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    albums: Array
});
</script>

<style scoped>
.header-container {
    @apply flex justify-between items-center;
}

.header-title {
    @apply font-semibold text-xl text-gray-800 leading-tight;
}

.create-button {
    @apply px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700;
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
    @apply bg-white overflow-hidden shadow-sm sm:rounded-lg;
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
    @apply text-lg font-semibold;
}

.album-description {
    @apply text-gray-600 mt-2;
}
</style>