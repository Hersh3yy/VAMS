<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Props {
    stats: {
        totalAlbums: number;
        totalMosaics: number;
        totalImages: number;
        totalVideos: number;
    };
    recentAlbums: Array<{
        id: number;
        title: string;
        description?: string;
        cover_image_path?: string;
        images_count: number;
        created_at: string;
    }>;
    recentMosaics: Array<{
        id: string;
        title: string;
        description?: string;
        items_count: number;
        created_at: string;
    }>;
}

defineProps<Props>();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <h2 class="page-title">
                    Welcome back!
                </h2>
                <div class="flex gap-4">
                    <Link :href="route('albums.create')" class="btn-primary">
                        Create Album
                    </Link>
                    <Link :href="route('mosaics.create')" class="btn-secondary">
                        Create Mosaic
                    </Link>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="card">
                        <div class="card-content">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-yellow-400">Albums</p>
                                    <p class="text-3xl font-bold text-secondary">{{ stats.totalAlbums }}</p>
                                </div>
                                <div class="bg-secondary bg-opacity-20 p-3 rounded-full">
                                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-content">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-yellow-400">Mosaics</p>
                                    <p class="text-3xl font-bold text-secondary">{{ stats.totalMosaics }}</p>
                                </div>
                                <div class="bg-secondary bg-opacity-20 p-3 rounded-full">
                                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-content">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-yellow-400">Images</p>
                                    <p class="text-3xl font-bold text-secondary">{{ stats.totalImages }}</p>
                                </div>
                                <div class="bg-secondary bg-opacity-20 p-3 rounded-full">
                                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-content">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-yellow-400">Videos</p>
                                    <p class="text-3xl font-bold text-secondary">{{ stats.totalVideos }}</p>
                                </div>
                                <div class="bg-secondary bg-opacity-20 p-3 rounded-full">
                                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Albums -->
                    <div class="card">
                        <div class="card-content">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-yellow-200">Recent Albums</h3>
                                <Link :href="route('albums.index')" class="text-secondary hover:text-secondary/80 text-sm font-medium">
                                    View all →
                                </Link>
                            </div>
                            
                            <div v-if="recentAlbums.length === 0" class="text-center py-8">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <p class="text-gray-500 dark:text-yellow-400">No albums yet</p>
                                <Link :href="route('albums.create')" class="btn-primary mt-4 inline-block">
                                    Create your first album
                                </Link>
                            </div>
                            
                            <div v-else class="space-y-4">
                                <Link 
                                    v-for="album in recentAlbums" 
                                    :key="album.id"
                                    :href="route('albums.show', album.id)"
                                    class="flex items-center space-x-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex-shrink-0">
                                        <img 
                                            v-if="album.cover_image_path"
                                            :src="album.cover_image_path" 
                                            :alt="album.title"
                                            class="w-12 h-12 rounded-lg object-cover"
                                        >
                                        <div 
                                            v-else
                                            class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center"
                                        >
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-yellow-200 truncate">{{ album.title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-yellow-400">{{ album.images_count }} items · {{ album.created_at }}</p>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Mosaics -->
                    <div class="card">
                        <div class="card-content">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-yellow-200">Recent Mosaics</h3>
                                <Link :href="route('mosaics.index')" class="text-secondary hover:text-secondary/80 text-sm font-medium">
                                    View all →
                                </Link>
                            </div>
                            
                            <div v-if="recentMosaics.length === 0" class="text-center py-8">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                </svg>
                                <p class="text-gray-500 dark:text-yellow-400">No mosaics yet</p>
                                <Link :href="route('mosaics.create')" class="btn-primary mt-4 inline-block">
                                    Create your first mosaic
                                </Link>
                            </div>
                            
                            <div v-else class="space-y-4">
                                <Link 
                                    v-for="mosaic in recentMosaics" 
                                    :key="mosaic.id"
                                    :href="route('mosaics.edit', mosaic.id)"
                                    class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-secondary hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-yellow-200 truncate">{{ mosaic.title }}</p>
                                            <p v-if="mosaic.description" class="text-xs text-gray-500 dark:text-yellow-400 mt-1 line-clamp-2">{{ mosaic.description }}</p>
                                            <p class="text-xs text-gray-500 dark:text-yellow-400 mt-2">{{ mosaic.items_count }} items · {{ mosaic.created_at }}</p>
                                        </div>
                                        <div class="flex-shrink-0 ml-4">
                                            <div class="w-12 h-8 bg-gray-100 dark:bg-gray-700 rounded border grid grid-cols-2 gap-0.5 p-1">
                                                <div class="bg-gray-300 dark:bg-gray-600 rounded-sm"></div>
                                                <div class="bg-gray-200 dark:bg-gray-500 rounded-sm"></div>
                                                <div class="bg-gray-200 dark:bg-gray-500 rounded-sm"></div>
                                                <div class="bg-gray-300 dark:bg-gray-600 rounded-sm"></div>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
