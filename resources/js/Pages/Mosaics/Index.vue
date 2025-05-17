<template>
    <Head title="Mosaics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="header-container">
                <h2 class="header-title">
                    Mosaics
                </h2>
                <Link
                    :href="route('mosaics.create')"
                    class="create-button"
                >
                    Create New Mosaic
                </Link>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="mosaics-container">
                <div v-if="mosaics.length === 0" class="empty-state">
                    <p>No mosaics found. Create your first mosaic to get started.</p>
                </div>
                
                <div v-else-if="mosaics.length === 1" class="single-mosaic">
                    <div class="mosaic-card">
                        <div class="mosaic-header">
                            <div>
                                <h3 class="mosaic-title">{{ mosaics[0].title || 'Untitled Mosaic' }}</h3>
                                <p class="mosaic-description">{{ mosaics[0].description || 'No description' }}</p>
                            </div>
                            <div class="action-buttons">
                                <Link :href="route('mosaics.edit', mosaics[0].id)"
                                    class="edit-button">
                                    Edit
                                </Link>
                                <button @click="deleteMosaic(mosaics[0].id)"
                                    class="delete-button">
                                    Delete
                                </button>
                            </div>
                        </div>
                        <div class="mosaic-preview">
                            <div v-for="item in mosaics[0].items.slice(0, 3)" :key="item.id"
                                class="preview-item">
                                <img v-if="item.image_path" :src="item.image_path" 
                                    :alt="item.title"
                                    class="preview-image">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="mosaics-grid">
                    <div v-for="mosaic in mosaics" :key="mosaic.id" class="mosaic-card">
                        <div class="mosaic-header">
                            <div>
                                <h3 class="mosaic-title">{{ mosaic.title || 'Untitled Mosaic' }}</h3>
                                <p class="mosaic-description">{{ mosaic.description || 'No description' }}</p>
                            </div>
                            <div class="action-buttons">
                                <Link :href="route('mosaics.edit', mosaic.id)"
                                    class="edit-button">
                                    Edit
                                </Link>
                                <button @click="deleteMosaic(mosaic.id)"
                                    class="delete-button">
                                    Delete
                                </button>
                            </div>
                        </div>
                        <div class="mosaic-preview">
                            <div v-for="item in mosaic.items.slice(0, 3)" :key="item.id"
                                class="preview-item">
                                <img v-if="item.image_path" :src="item.image_path" 
                                    :alt="item.title"
                                    class="preview-image">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    mosaics: Array
});

const deleteMosaic = (id) => {
    if (confirm('Are you sure you want to delete this mosaic?')) {
        router.delete(route('mosaics.destroy', id));
    }
};
</script>

<style scoped>
.header-container {
    @apply flex justify-between items-center;
}

.header-title {
    @apply font-semibold text-xl text-gray-800 leading-tight;
}

.create-button {
    @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded;
}

.content-wrapper {
    @apply py-12;
}

.mosaics-container {
    @apply max-w-7xl mx-auto sm:px-6 lg:px-8;
}

.empty-state {
    @apply text-center py-10 bg-white shadow-sm rounded-lg;
}

.single-mosaic {
    @apply w-full max-w-2xl mx-auto;
}

.mosaics-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.mosaic-card {
    @apply bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-lg transition-shadow p-6;
}

.mosaic-header {
    @apply flex justify-between items-start;
}

.mosaic-title {
    @apply text-lg font-semibold;
}

.mosaic-description {
    @apply text-gray-600 mt-1;
}

.action-buttons {
    @apply flex gap-2;
}

.edit-button {
    @apply text-blue-600 hover:text-blue-800;
}

.delete-button {
    @apply text-red-600 hover:text-red-800;
}

.mosaic-preview {
    @apply mt-4 grid grid-cols-3 gap-2;
}

.preview-item {
    @apply aspect-square bg-gray-100 rounded overflow-hidden;
}

.preview-image {
    @apply w-full h-full object-cover;
}
</style> 