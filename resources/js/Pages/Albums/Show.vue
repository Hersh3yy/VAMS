<template>
    <Head :title="album.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <Link :href="route('albums.index')" 
                        class="mr-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-full inline-flex items-center transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ album.title }}</h2>
                </div>
                <div class="flex space-x-3">
                    <button @click="confirmDeleteAlbum" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Delete Album
                    </button>
                    <Link :href="route('albums.edit', album.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Edit Album
                    </Link>
                <label class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer">
                    Add Images
                    <input type="file" class="hidden" multiple accept="image/*" @change="handleFileUpload">
                </label>
                    <button @click="openAddVideoModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Add Video
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-600 mb-6">{{ album.description }}</p>

                    <div v-if="uploading" class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: `${uploadProgress}%` }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div v-for="image in images" :key="image.id"
                            class="aspect-square relative bg-gray-100 rounded-lg overflow-hidden cursor-move group"
                            :class="{
                                'opacity-50 ring-4 ring-blue-500': isDragging && draggedImage?.id === image.id,
                                'ring-4 ring-green-500': isDragOver && draggedImage?.id !== image.id
                            }"
                            draggable="true"
                            @click="openModal(image)"
                            @dragstart="handleDragStart($event, image)"
                            @dragend="handleDragEnd"
                            @dragover.prevent
                            @dragenter.prevent="handleDragEnter($event, image)"
                            @dragleave.prevent="handleDragLeave"
                            @drop.prevent="handleDrop($event, image)"
                        >
                            <!-- Video badge -->
                            <div v-if="isVideo(image.path)" class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 z-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            
                            <!-- Delete button -->
                            <button 
                                @click.stop="confirmDeleteImage(image)" 
                                class="absolute top-2 left-2 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity z-10"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                            
                            <img :src="getImageSrc(image)" :alt="image.title || 'Album image'"
                                class="object-cover w-full h-full transition-transform duration-200 group-hover:scale-105">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <ImageModal 
            :show="showModal" 
            :image="selectedImage" 
            :display-settings="auth.user.album_display_settings"
            @close="closeModal" 
            @update="handleImageUpdate" 
            @delete="confirmDeleteImage"
        />
        
        <!-- Video Modal -->
        <div v-if="showVideoModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform w-full max-w-4xl">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-medium">{{ selectedVideo?.title || 'Video' }}</h3>
                    <button @click="closeVideoModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <div class="aspect-video">
                        <iframe
                            v-if="videoEmbedUrl"
                            :src="videoEmbedUrl"
                            class="w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </div>
                    <div v-if="selectedVideo?.caption" class="mt-4 p-4 bg-gray-100 rounded">
                        <p>{{ selectedVideo.caption }}</p>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-end">
                    <button @click="confirmDeleteImage(selectedVideo)" class="text-red-600 hover:text-red-800 mr-4">
                        Delete Video
                    </button>
                    <button @click="closeVideoModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Close
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Confirmation Dialog -->
        <div v-if="showConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium mb-4">{{ confirmationTitle }}</h3>
                <p>{{ confirmationMessage }}</p>
                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="cancelConfirmation" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100">
                        Cancel
                    </button>
                    <button @click="confirmAction" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Video Modal -->
        <div v-if="showAddVideoModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform w-full max-w-md">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-medium">Add Video</h3>
                    <button @click="closeAddVideoModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="videoUrl">
                            Video URL (YouTube or Vimeo)
                        </label>
                        <input 
                            id="videoUrl" 
                            v-model="videoUrl" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                            placeholder="https://www.youtube.com/watch?v=..."
                        >
                        <p class="text-sm text-gray-500 mt-2">Supported formats: YouTube and Vimeo links</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="videoTitle">
                            Title (optional)
                        </label>
                        <input 
                            id="videoTitle" 
                            v-model="videoTitle" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                            placeholder="Video title"
                        >
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="videoCaption">
                            Caption (optional)
                        </label>
                        <textarea 
                            id="videoCaption" 
                            v-model="videoCaption" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            rows="3"
                            placeholder="Video description"
                        ></textarea>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-end">
                    <button @click="closeAddVideoModal" class="text-gray-600 hover:text-gray-800 mr-4">
                        Cancel
                    </button>
                    <button @click="addVideo" :disabled="!isValidVideoUrl" :class="{'opacity-50 cursor-not-allowed': !isValidVideoUrl}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add Video
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageModal from '@/Components/ImageModal.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    album: Object,
    images: Array,
    auth: Object,
});

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedImage = ref(null);
const images = ref(props.images);
const uploading = ref(false);
const uploadProgress = ref(0);
const showModal = ref(false);
const selectedImage = ref(null);
const showVideoModal = ref(false);
const selectedVideo = ref(null);
const showConfirmation = ref(false);
const confirmationTitle = ref('');
const confirmationMessage = ref('');
const confirmationAction = ref(null);
const videoUrl = ref('');
const videoTitle = ref('');
const videoCaption = ref('');
const showAddVideoModal = ref(false);

// Video modal handling
const openVideoModal = (image) => {
    selectedVideo.value = image;
    showVideoModal.value = true;
};

const closeVideoModal = () => {
    showVideoModal.value = false;
    selectedVideo.value = null;
};

const videoEmbedUrl = computed(() => {
    if (!selectedVideo.value) return null;
    
    const url = selectedVideo.value.path;
    
    // YouTube
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
        let videoId;
        
        if (url.includes('youtube.com')) {
            const params = new URLSearchParams(new URL(url).search);
            videoId = params.get('v');
        } else if (url.includes('youtu.be')) {
            videoId = url.split('/').pop().split('?')[0];
        }
        
        if (videoId) {
            return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
        }
    }
    
    // Vimeo
    if (url.includes('vimeo.com')) {
        const vimeoId = url.split('/').pop();
        if (vimeoId) {
            return `https://player.vimeo.com/video/${vimeoId}?autoplay=1`;
        }
    }
    
    return null;
});

// Confirmation dialog
const confirmDeleteImage = (image) => {
    confirmationTitle.value = 'Delete Image';
    confirmationMessage.value = 'Are you sure you want to delete this image? This action cannot be undone.';
    confirmationAction.value = () => deleteImage(image);
    showConfirmation.value = true;
};

const confirmDeleteAlbum = () => {
    confirmationTitle.value = 'Delete Album';
    confirmationMessage.value = `Are you sure you want to delete the album "${props.album.title}" and all its images? This action cannot be undone.`;
    confirmationAction.value = deleteAlbum;
    showConfirmation.value = true;
};

const cancelConfirmation = () => {
    showConfirmation.value = false;
    confirmationAction.value = null;
};

const confirmAction = () => {
    if (confirmationAction.value) {
        confirmationAction.value();
    }
    showConfirmation.value = false;
};

// Delete actions
const deleteImage = async (image) => {
    try {
        await axios.delete(route('album-images.destroy', image.id));
        
        // Remove from local state
        images.value = images.value.filter(img => img.id !== image.id);
        
        // Close any open modals
        closeModal();
        closeVideoModal();
    } catch (error) {
        console.error('Failed to delete image:', error);
    }
};

const deleteAlbum = () => {
    router.delete(route('albums.destroy', props.album.id));
};

// Existing methods
const openModal = (image) => {
    if (isVideo(image.path)) {
        openVideoModal(image);
    } else {
    selectedImage.value = image;
    showModal.value = true;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedImage.value = null;
};

// Utility methods
const isVideo = (path) => {
    return path && (
        path.includes('youtube.com') || 
        path.includes('youtu.be') || 
        path.includes('vimeo.com')
    );
};

const getImageSrc = (image) => {
    if (!image) return '';
    
    // For videos, try to get thumbnail from properties
    if (isVideo(image.path)) {
        try {
            const properties = typeof image.properties === 'string' 
                ? JSON.parse(image.properties) 
                : image.properties;
                
            if (properties && properties.thumbnail_url) {
                return properties.thumbnail_url;
            }
            
            // Generate YouTube thumbnail if possible
            if (image.path.includes('youtube.com') || image.path.includes('youtu.be')) {
                let videoId;
                
                if (image.path.includes('youtube.com')) {
                    const params = new URLSearchParams(new URL(image.path).search);
                    videoId = params.get('v');
                } else if (image.path.includes('youtu.be')) {
                    videoId = image.path.split('/').pop().split('?')[0];
                }
                
                if (videoId) {
                    return `https://img.youtube.com/vi/${videoId}/mqdefault.jpg`;
                }
            }
        } catch (e) {
            console.error('Error parsing image properties:', e);
        }
        
        // Default video placeholder - inline SVG as data URI
        return `data:image/svg+xml;charset=utf-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full text-gray-400"><rect x="10" y="10" width="80" height="80" rx="5" ry="5" fill="%23333"/><circle cx="50" cy="50" r="20" fill="%23555"/><polygon points="45,40 60,50 45,60" fill="white"/></svg>`;
    }
    
    return image.path;
};

// ... rest of the existing methods (handleDragStart, etc) ...

const handleDragStart = (event, image) => {
    isDragging.value = true;
    draggedImage.value = image;
    event.dataTransfer.effectAllowed = 'move';
};

const handleDragEnd = () => {
    isDragging.value = false;
    draggedImage.value = null;
    isDragOver.value = false;
};

const handleDragEnter = (event, image) => {
    if (draggedImage.value?.id !== image.id) {
        isDragOver.value = true;
    }
};

const handleDragLeave = () => {
    isDragOver.value = false;
};

const handleDrop = async (event, targetImage) => {
    isDragOver.value = false;
    if (!draggedImage.value || draggedImage.value.id === targetImage.id) return;

    const newImages = [...images.value];
    const draggedIdx = newImages.findIndex(img => img.id === draggedImage.value.id);
    const targetIdx = newImages.findIndex(img => img.id === targetImage.id);

    // Update local state immediately for smooth UI
    newImages.splice(draggedIdx, 1);
    newImages.splice(targetIdx, 0, draggedImage.value);
    images.value = newImages;

    try {
        await router.post(route('album-images.reorder'), {
            image_id: draggedImage.value.id,
            new_order: targetIdx
        });
    } catch (error) {
        console.error('Failed to reorder images:', error);
        // Optionally revert the change if the server update fails
        images.value = props.images;
    }
};

const handleImageUpdate = (updatedImage) => {
    const index = images.value.findIndex(img => img.id === updatedImage.id);
    if (index !== -1) {
        images.value[index] = updatedImage;
    }
};

const handleFileUpload = async (event) => {
    const files = event.target.files;
    if (!files.length) return;

    uploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    formData.append('album_id', props.album.id);
    Array.from(files).forEach(file => {
        formData.append('images[]', file);
    });

    try {
        const response = await axios.post(route('album-images.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round(
                    (progressEvent.loaded * 100) / progressEvent.total
                );
            }
        });

        images.value = [...images.value, ...response.data];
    } catch (error) {
        console.error('Upload failed:', error);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
};

const isValidVideoUrl = computed(() => {
    const url = videoUrl.value;
    return url && (
        url.includes('youtube.com') || 
        url.includes('youtu.be') || 
        url.includes('vimeo.com')
    );
});

const openAddVideoModal = () => {
    videoUrl.value = '';
    videoTitle.value = '';
    videoCaption.value = '';
    showAddVideoModal.value = true;
};

const closeAddVideoModal = () => {
    showAddVideoModal.value = false;
};

const addVideo = async () => {
    if (!isValidVideoUrl.value) return;
    
    try {
        const response = await axios.post(route('album-images.store-video'), {
            album_id: props.album.id,
            url: videoUrl.value,
            title: videoTitle.value,
            caption: videoCaption.value
        });
        
        // Add the new video to the images list
        images.value.push(response.data);
        
        // Close the modal
        closeAddVideoModal();
    } catch (error) {
        console.error('Failed to add video:', error);
    }
};
</script>

<style scoped>
.group {
    @apply transition-all duration-200;
}

.group:hover {
    @apply ring-4 ring-blue-300;
}
</style>