<template>
    <Head title="Mosaics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">
                    Mosaics
                </h2>
                <Link 
                    :href="route('mosaics.create')"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                >
                    Create New Mosaic
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="mosaics.length === 0" class="bg-white rounded-lg shadow p-6 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Mosaics Yet</h3>
                    <p class="text-gray-600 mb-4">Create your first mosaic layout to get started.</p>
                    <Link 
                        :href="route('mosaics.create')"
                        class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        Create First Mosaic
                    </Link>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="mosaic in mosaics" 
                        :key="mosaic.id"
                        class="bg-white rounded-lg shadow overflow-hidden group"
                    >
                        <div class="aspect-video bg-gray-100 relative">
                            <!-- Mosaic Preview -->
                            <div class="absolute inset-0 grid grid-cols-2 gap-1 p-2">
                                <div class="bg-gray-200 rounded"></div>
                                <div class="bg-gray-300 rounded"></div>
                                <div class="bg-gray-300 rounded"></div>
                                <div class="bg-gray-200 rounded"></div>
                            </div>
                            
                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                                <Link 
                                    :href="route('mosaics.edit', mosaic.id)"
                                    class="p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600"
                                    title="Edit Mosaic"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                    </svg>
                                </Link>
                                <button 
                                    @click="deleteMosaic(mosaic)"
                                    class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600"
                                    title="Delete Mosaic"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900">{{ mosaic.title }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ mosaic.description || 'No description' }}</p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    Created {{ new Date(mosaic.created_at).toLocaleDateString() }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ mosaic.items?.length || 0 }} tiles
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    mosaics: {
        id: string;
        title: string;
        description: string | null;
        created_at: string;
        items: any[];
    }[];
}>();

const deleteMosaic = (mosaic: any) => {
    if (confirm(`Are you sure you want to delete "${mosaic.title}"?`)) {
        router.delete(route('mosaics.destroy', mosaic.id));
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