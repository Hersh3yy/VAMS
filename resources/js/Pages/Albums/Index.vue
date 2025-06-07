<template>
    <Head title="Albums" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <h2 class="page-title">Albums</h2>
                <Link :href="route('albums.create')" class="btn-primary">
                    Create Album
                </Link>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <div class="items-grid">
                    <div v-for="album in albums" :key="album.id" class="card">
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
                            <div class="card-content">
                                <h3 class="text-lg font-semibold dark:text-yellow-200">{{ album.title }}</h3>
                                <p class="text-gray-600 mt-2 dark:text-yellow-400">{{ album.description || 'No description' }}</p>
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
/* Component-specific styles only */
</style>