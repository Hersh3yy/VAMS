<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Album {
    id: string;
    title: string;
    cover_image_path: string | null;
    images_count: number;
    description?: string;
}

defineProps<{
    recentAlbums: Album[];
}>();
</script>

<template>
    <Head title="VAMS - Visual Album Management System" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard
                </h2>
                <Link 
                    :href="route('albums.create')" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition duration-150 ease-in-out flex items-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create New Album
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Recent Albums Section -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 mb-8">
                    <div class="p-6">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100 mb-6">Recently Updated Albums</h3>
                        
                        <div v-if="recentAlbums && recentAlbums.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-6">
                            <div v-for="album in recentAlbums" :key="album.id" 
                                class="bg-gray-50 dark:bg-gray-700 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-[1.02]">
                                <Link :href="route('albums.show', album.id)" class="block h-full">
                                    <div class="h-48 bg-gray-200 dark:bg-gray-600 overflow-hidden">
                                        <img 
                                            :src="album.cover_image_path || '/placeholder.jpg'" 
                                            :alt="album.title" 
                                            class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                                        >
                                    </div>
                                    <div class="p-5">
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ album.title }}</h4>
                                        <p v-if="album.description" class="text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                                            {{ album.description }}
                                        </p>
                                        <div class="flex items-center mt-3 text-sm text-gray-500 dark:text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ album.images_count || 0 }} images
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">No albums found.</p>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">Create your first album to get started!</p>
                            <Link 
                                :href="route('albums.create')" 
                                class="mt-6 inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition duration-300"
                            >
                                Create Your First Album
                            </Link>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100 mb-6">Quick Links</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Link 
                                :href="route('albums.index')" 
                                class="p-6 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-xl flex items-center transition-colors duration-300"
                            >
                                <div class="p-3 bg-indigo-600 rounded-lg mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Browse All Albums</h4>
                                    <p class="text-gray-600 dark:text-gray-400 mt-1">View and manage all your photo and video albums</p>
                                </div>
                            </Link>
                            
                            <Link 
                                :href="route('mosaics.index')" 
                                class="p-6 bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 rounded-xl flex items-center transition-colors duration-300"
                            >
                                <div class="p-3 bg-purple-600 rounded-lg mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Manage Mosaics</h4>
                                    <p class="text-gray-600 dark:text-gray-400 mt-1">Create and organize visual layouts for your content</p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
