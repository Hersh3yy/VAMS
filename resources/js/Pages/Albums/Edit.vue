<template>
    <Head title="Edit Album" />

    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <div class="flex items-center">
                    <Link :href="route('albums.show', album.id)" 
                        class="mr-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-full inline-flex items-center transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                    <h2 class="page-title">Edit Album</h2>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <div class="card">
                    <div class="card-content">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Title *
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    v-model="form.title"
                                    required
                                    autofocus
                                    placeholder="Enter album title"
                                />
                                <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Enter album description"
                                ></textarea>
                                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div>
                                <label for="cover_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Cover Image
                                </label>
                                <div class="mt-2 space-y-4">
                                    <!-- Current and New Image Display -->
                                    <div class="flex items-start space-x-4">
                                        <!-- Current Cover Image -->
                                        <div v-if="album.cover_image_path && !coverImagePreview" class="flex-shrink-0">
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current cover:</div>
                                            <div class="image-container w-32 h-32 rounded-lg border-2 border-gray-200 dark:border-gray-700 overflow-hidden">
                                                <img 
                                                    :src="album.cover_image_path" 
                                                    class="cover-image"
                                                    alt="Current cover"
                                                />
                                            </div>
                                        </div>
                                        
                                        <!-- New Image Preview -->
                                        <div v-if="coverImagePreview" class="flex-shrink-0">
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">New cover preview:</div>
                                            <div class="image-container w-32 h-32 rounded-lg border-2 border-secondary overflow-hidden">
                                                <img 
                                                    :src="coverImagePreview" 
                                                    class="cover-image"
                                                    alt="New cover preview"
                                                />
                                            </div>
                                        </div>
                                        
                                        <!-- File Input -->
                                        <div class="flex-1">
                                            <input 
                                                type="file" 
                                                id="cover_image"
                                                @change="handleFileChange"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                accept="image/*"
                                            />
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ album.cover_image_path ? 'Choose a new image to replace the current cover.' : 'Choose an image for the album cover.' }}
                                                PNG, JPG, GIF up to 10MB.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.cover_image" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.cover_image }}
                                </div>
                            </div>

                            <!-- Select from Album Images -->
                            <div v-if="album.images && album.images.length > 0">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Or Select from Album Images
                                </label>
                                
                                <!-- Selected Cover Image Display -->
                                <div v-if="form.selected_cover_image_id" class="mb-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Selected as cover:</div>
                                            <div class="image-container w-32 h-32 rounded-lg border-2 border-secondary overflow-hidden">
                                                <img 
                                                    :src="album.images.find(img => img.id === form.selected_cover_image_id)?.path"
                                                    class="cover-image"
                                                    alt="Selected cover"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <button 
                                                @click="clearSelectedCoverImage"
                                                class="btn btn-secondary text-sm"
                                            >
                                                Clear Selection
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Image Selector -->
                                <div>
                                    <button 
                                        @click="showImageSelector = !showImageSelector"
                                        class="btn btn-secondary"
                                        type="button"
                                    >
                                        {{ showImageSelector ? 'Hide Images' : 'Choose from Album Images' }}
                                    </button>
                                    
                                    <div v-if="showImageSelector" class="mt-4 grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3 max-h-64 overflow-y-auto border rounded-lg p-4">
                                        <div 
                                            v-for="image in album.images" 
                                            :key="image.id"
                                            @click="selectCoverImage(image)"
                                            class="relative cursor-pointer rounded-lg overflow-hidden hover:ring-2 hover:ring-secondary transition-all"
                                            :class="{ 'ring-2 ring-secondary': form.selected_cover_image_id === image.id }"
                                        >
                                            <div class="aspect-square">
                                                <img 
                                                    :src="getImageUrl(image)" 
                                                    :alt="image.title || 'Album image'"
                                                    class="w-full h-full object-cover"
                                                />
                                                <!-- Video badge -->
                                                <div v-if="isVideoItem(image)" class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 z-10">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Click on an image or video thumbnail to select it as the album cover.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <Link 
                                    :href="route('albums.show', album.id)"
                                    class="btn btn-secondary"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    class="btn-primary"
                                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Updating...
                                    </span>
                                    <span v-else>Update Album</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    album: Object,
});

const form = useForm({
    title: props.album.title,
    description: props.album.description || '',
    cover_image: null,
    selected_cover_image_id: null
});

const coverImagePreview = ref(null);
const showImageSelector = ref(false);

const selectCoverImage = (image) => {
    form.selected_cover_image_id = image.id;
    showImageSelector.value = false;
};

const clearSelectedCoverImage = () => {
    form.selected_cover_image_id = null;
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.cover_image = file;
        // Create preview URL
        const reader = new FileReader();
        reader.onload = (e) => {
            coverImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.cover_image = null;
        coverImagePreview.value = null;
    }
};

const submit = () => {
    form.patch(route('albums.update', props.album.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset the file input and preview
            form.cover_image = null;
            coverImagePreview.value = null;
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        }
    });
};

// Video handling functions (shared with ImageSelectionModal)
const isVideoItem = (image) => {
    if (image.properties) {
        const properties = typeof image.properties === 'string' 
            ? JSON.parse(image.properties) 
            : image.properties;
        
        return properties?.type === 'video';
    }
    
    // Fallback check based on path
    return image.path?.includes('youtube.com') || 
           image.path?.includes('youtu.be') || 
           image.path?.includes('vimeo.com');
};

const getImageUrl = (image) => {
    // Try to get thumbnail URL from properties (for videos)
    if (image.properties) {
        const properties = typeof image.properties === 'string' 
            ? JSON.parse(image.properties) 
            : image.properties;
            
        if (properties?.thumbnail_url) {
            return properties.thumbnail_url;
        }
    }
    
    // Fallback to regular path
    return image.path;
};
</script> 