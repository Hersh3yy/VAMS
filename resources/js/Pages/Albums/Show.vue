<template>
    <Head :title="album.title" />

    <AuthenticatedLayout>
        <template #header>
            <AlbumHeader
                :album="album"
                @delete="confirmDeleteAlbum"
                @upload="handleFileUpload"
                @add-video="openAddVideoModal"
            />
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

                    <AlbumGrid
                        :items="images"
                        @item-click="openModal"
                        @item-delete="confirmDeleteImage"
                        @reorder="reorderImages"
                    />
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <ImageModal 
            v-if="selectedImage"
            :show="showModal" 
            :image="selectedImage" 
            :display-settings="auth.user.album_display_settings"
            @close="closeModal" 
            @update="handleImageUpdate" 
            @delete="confirmDeleteImage"
        />
        
        <!-- Video Modal -->
        <div v-if="showVideoModal && selectedVideo" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform w-full max-w-4xl">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-medium">{{ selectedVideo.title || 'Video' }}</h3>
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
                    <div v-if="selectedVideo.caption" class="mt-4 p-4 bg-gray-100 rounded">
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
                    <button 
                        @click="handleAddVideo" 
                        :disabled="!isValidVideoUrl" 
                        :class="{'opacity-50 cursor-not-allowed': !isValidVideoUrl}" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Add Video
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageModal from '@/Components/ImageModal.vue';
import AlbumHeader from '@/Components/albums/AlbumHeader.vue';
import AlbumGrid from '@/Components/albums/AlbumGrid.vue';
import { ref, computed } from 'vue';
import { useAlbum } from '@/composables/albums/useAlbum';
import type { AlbumImage, AlbumVideo } from '@/types/album';

const props = defineProps<{
    album: {
        id: number;
        title: string;
        description: string;
        created_at: string;
        updated_at: string;
    };
    images: (AlbumImage | AlbumVideo)[];
    auth: {
        user: {
            album_display_settings: {
                grid_columns: number;
                show_titles: boolean;
                show_captions: boolean;
            };
        };
    };
}>();

const {
    uploading,
    uploadProgress,
    showConfirmation,
    confirmationTitle,
    confirmationMessage,
    handleFileUpload,
    deleteAlbum,
    deleteImage,
    reorderImages,
    addVideo,
    showConfirmationDialog,
    confirmAction,
    cancelConfirmation,
} = useAlbum(props.album.id);

const showModal = ref(false);
const selectedImage = ref<AlbumImage | null>(null);
const showVideoModal = ref(false);
const selectedVideo = ref<AlbumVideo | null>(null);
const videoUrl = ref('');
const videoTitle = ref('');
const videoCaption = ref('');
const showAddVideoModal = ref(false);

const videoEmbedUrl = computed(() => {
    if (!selectedVideo.value) return null;
    return selectedVideo.value.embed_url;
});

const isValidVideoUrl = computed(() => {
    if (!videoUrl.value) return false;
    return /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+/.test(videoUrl.value);
});

const openModal = (image: AlbumImage | AlbumVideo) => {
    if ('embed_url' in image) {
        selectedVideo.value = image;
        showVideoModal.value = true;
    } else {
        selectedImage.value = image;
        showModal.value = true;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedImage.value = null;
};

const closeVideoModal = () => {
    showVideoModal.value = false;
    selectedVideo.value = null;
};

const handleImageUpdate = (updatedImage: AlbumImage) => {
    const index = props.images.findIndex(img => img.id === updatedImage.id);
    if (index !== -1) {
        props.images[index] = updatedImage;
    }
};

const confirmDeleteAlbum = () => {
    showConfirmationDialog(
        'Delete Album',
        'Are you sure you want to delete this album? This action cannot be undone.',
        deleteAlbum
    );
};

const confirmDeleteImage = (image: AlbumImage | AlbumVideo) => {
    showConfirmationDialog(
        'Delete Item',
        'Are you sure you want to delete this item? This action cannot be undone.',
        () => deleteImage(image.id)
    );
};

const openAddVideoModal = () => {
    showAddVideoModal.value = true;
};

const closeAddVideoModal = () => {
    showAddVideoModal.value = false;
    videoUrl.value = '';
    videoTitle.value = '';
    videoCaption.value = '';
};

const handleAddVideo = async () => {
    if (!isValidVideoUrl.value) return;
    
    await addVideo(videoUrl.value, videoTitle.value, videoCaption.value);
    closeAddVideoModal();
};
</script>