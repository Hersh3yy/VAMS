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

        <!-- Album Cover Section -->
        <AlbumCover :album="album" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div v-if="uploading" class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Uploading...</span>
                            <span>{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div 
                                class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                                :style="{ width: `${uploadProgress}%` }"
                            ></div>
                        </div>
                    </div>

                    <AlbumGrid
                        :items="album.images || []"
                        @item-click="openModal"
                        @item-delete="confirmDeleteImage"
                        @reorder="handleReorder"
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
                    <button @click="closeVideoModal" class="btn-primary">
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
        <VideoWizard
            v-if="showAddVideoModal"
            :show="showAddVideoModal"
            @close="closeAddVideoModal"
            @save="handleAddVideo"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageModal from '@/Components/albums/ImageModal.vue';
import AlbumHeader from '@/Components/albums/AlbumHeader.vue';
import AlbumGrid from '@/Components/albums/AlbumGrid.vue';
import AlbumCover from '@/Components/albums/AlbumCover.vue';
import VideoWizard from '@/Components/albums/VideoWizard.vue';
import { ref, computed } from 'vue';
import { useAlbum } from '@/composables/albums/useAlbum';
import type { Album, AlbumImage } from '@/types/album';

const props = defineProps<{
    album: Album;
    auth: {
        user: {
            id: string;
            name: string;
            email: string;
            logo_url: string | null;
            is_admin: boolean;
            album_display_settings?: {
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
const selectedVideo = ref<AlbumImage | null>(null);
const videoUrl = ref('');
const videoTitle = ref('');
const videoCaption = ref('');
const showAddVideoModal = ref(false);

const videoEmbedUrl = computed(() => {
    if (!selectedVideo.value) return null;
    if (selectedVideo.value.properties?.type === 'video') {
        const url = selectedVideo.value.properties.video_url || selectedVideo.value.path;
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
    }
    return null;
});

const isValidVideoUrl = computed(() => {
    if (!videoUrl.value) return false;
    return /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+/.test(videoUrl.value);
});

const openModal = (image: AlbumImage) => {
    if (image.properties?.type === 'video') {
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
    if (!props.album.images) return;
    const index = props.album.images.findIndex(img => img.id === updatedImage.id);
    if (index !== -1) {
        props.album.images[index] = updatedImage;
    }
};

const confirmDeleteAlbum = () => {
    showConfirmationDialog(
        'Delete Album',
        'Are you sure you want to delete this album? This action cannot be undone.',
        deleteAlbum
    );
};

const confirmDeleteImage = (image: AlbumImage) => {
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

const handleAddVideo = async (data: { url: string; title: string; caption: string }) => {
    await addVideo(data.url, data.title, data.caption);
    closeAddVideoModal();
};

const handleReorder = (fromIndex: number, toIndex: number) => {
    reorderImages(fromIndex, toIndex);
};
</script>