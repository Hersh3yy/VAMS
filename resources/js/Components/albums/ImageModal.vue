<!-- components/ImageModal.vue -->
<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium">{{ isVideo ? 'Edit Video' : 'Edit Image' }}</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div class="bg-white p-6">
                    <!-- Video Display -->
                    <div v-if="isVideo" class="mb-4">
                        <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                            <iframe
                                v-if="videoEmbedUrl"
                                :src="videoEmbedUrl"
                                class="w-full h-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-gray-500">Video Player</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Display -->
                    <div v-else class="mb-4">
                        <img :src="image.path" :alt="image.title || 'Image'" class="w-full h-auto max-h-[60vh] object-contain">
                    </div>
                    
                    <div class="mt-4 space-y-4">
                        <!-- Video-specific fields -->
                        <template v-if="isVideo">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Video URL</label>
                                <input 
                                    type="url" 
                                    v-model="formData.videoUrl" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    readonly
                                >
                            </div>
                            <div v-if="displaySettings?.title !== false">
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input 
                                    type="text" 
                                    v-model="formData.title" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                            <div v-if="displaySettings?.caption !== false">
                                <label class="block text-sm font-medium text-gray-700">Caption</label>
                                <textarea 
                                    v-model="formData.caption" 
                                    rows="3" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>
                        </template>
                        
                        <!-- Image-specific fields -->
                        <template v-else>
                            <div v-if="displaySettings?.title !== false">
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" v-model="formData.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div v-if="displaySettings?.altText !== false">
                                <label class="block text-sm font-medium text-gray-700">Alt Text</label>
                                <input type="text" v-model="formData.altText" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div v-if="displaySettings?.caption !== false">
                                <label class="block text-sm font-medium text-gray-700">Caption</label>
                                <textarea v-model="formData.caption" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                            <div v-if="displaySettings?.dateCreated !== false">
                                <label class="block text-sm font-medium text-gray-700">Date Created</label>
                                <input type="datetime-local" v-model="formData.dateCreated" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div v-if="displaySettings?.location !== false">
                                <label class="block text-sm font-medium text-gray-700">Location</label>
                                <input type="text" v-model="formData.location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div v-if="displaySettings?.tags !== false">
                                <label class="block text-sm font-medium text-gray-700">Tags</label>
                                <input type="text" v-model="formData.tags" placeholder="Separate tags with commas" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div v-if="displaySettings?.author !== false">
                                <label class="block text-sm font-medium text-gray-700">Author</label>
                                <input type="text" v-model="formData.author" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </template>
                        
                        <div class="flex justify-end space-x-3">
                            <button @click="$emit('close')" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button @click="saveChanges" class="btn-primary">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    image: {
        type: Object,
        required: true
    },
    displaySettings: {
        type: Object,
        default: () => ({
            title: true,
            caption: true,
            altText: true,
            dateCreated: true,
            location: true,
            tags: true,
            author: true,
        })
    }
});

const emit = defineEmits(['close', 'update']);

const formData = ref({
    title: '',
    altText: '',
    caption: '',
    dateCreated: '',
    location: '',
    tags: '',
    author: '',
    videoUrl: '',
});

const isVideo = computed(() => {
    if (!props.image?.properties) return false;
    
    // Handle both string and object properties
    const properties = typeof props.image.properties === 'string' 
        ? JSON.parse(props.image.properties) 
        : props.image.properties;
    
    return properties?.type === 'video';
});

const videoEmbedUrl = computed(() => {
    if (!isVideo.value) return null;
    
    // Handle both string and object properties
    const properties = typeof props.image.properties === 'string' 
        ? JSON.parse(props.image.properties) 
        : props.image.properties;
    
    const url = properties?.video_url || props.image?.path || '';
    
    // Convert to embed URL for YouTube/Vimeo
    if (url.includes('youtube.com/watch')) {
        const videoId = url.split('v=')[1]?.split('&')[0];
        return `https://www.youtube.com/embed/${videoId}`;
    } else if (url.includes('youtu.be/')) {
        const videoId = url.split('youtu.be/')[1]?.split('?')[0];
        return `https://www.youtube.com/embed/${videoId}`;
    } else if (url.includes('vimeo.com/')) {
        const videoId = url.split('vimeo.com/')[1]?.split('?')[0];
        return `https://player.vimeo.com/video/${videoId}`;
    }
    
    return null;
});

watch(() => props.image, (newImage) => {
    if (newImage) {
        // Handle both string and object properties
        const properties = typeof newImage.properties === 'string' 
            ? JSON.parse(newImage.properties) 
            : newImage.properties;
            
        formData.value = {
            title: newImage.title || '',
            altText: newImage.altText || '',
            caption: newImage.caption || '',
            dateCreated: newImage.dateCreated || '',
            location: newImage.location || '',
            tags: newImage.tags || '',
            author: newImage.author || '',
            videoUrl: properties?.video_url || newImage.path || '',
        };
    }
}, { immediate: true });

const saveChanges = async () => {
    try {
        const updateData = isVideo.value ? {
            title: formData.value.title,
            caption: formData.value.caption,
        } : {
            title: formData.value.title,
            altText: formData.value.altText,
            caption: formData.value.caption,
            dateCreated: formData.value.dateCreated,
            location: formData.value.location,
            tags: formData.value.tags,
            author: formData.value.author,
        };

        await router.put(route('albums.images.update', { album: props.image.album_id, image: props.image.id }), updateData, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                emit('update', { ...props.image, ...updateData });
                emit('close');
            },
            onError: (errors) => {
                console.error('Album image update error:', errors);
                // Handle error with user-friendly message
                alert('Unable to save changes. Please check the fields and try again.');
            }
        });
    } catch (error) {
        // Show user-friendly error message
        alert('Unable to save changes. Please try again.');
    }
};
</script>