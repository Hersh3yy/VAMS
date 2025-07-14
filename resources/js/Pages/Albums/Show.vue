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
                    <UploadProgress
                        :upload-queue="uploadQueue"
                        :completed-count="completedCount"
                        :error-count="errorCount"
                        :pending-count="pendingCount"
                        :overall-progress="overallProgress"
                        @retry="retryUpload"
                        @remove="removeFromQueue"
                        @clear-completed="clearCompletedUploads"
                    />

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
            :display-settings="album_display_settings"
            @close="closeModal" 
            @update="handleImageUpdate" 
            @delete="confirmDeleteImage"
        />
        
        <!-- Video Modal -->
        <VideoModal
            :show="showVideoModal"
            :video="selectedVideo"
            @close="closeVideoModal"
            @delete="confirmDeleteImage"
        />
        
        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            :show="showConfirmation"
            :title="confirmationTitle"
            :message="confirmationMessage"
            confirm-text="Delete"
            cancel-text="Cancel"
            @confirm="confirmAction"
            @cancel="cancelConfirmation"
        />

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
import VideoModal from '@/Components/albums/VideoModal.vue';
import AlbumHeader from '@/Components/albums/AlbumHeader.vue';
import AlbumGrid from '@/Components/albums/AlbumGrid.vue';
import AlbumCover from '@/Components/albums/AlbumCover.vue';
import VideoWizard from '@/Components/albums/VideoWizard.vue';
import ConfirmationDialog from '@/Components/shared/ConfirmationDialog.vue';
import UploadProgress from '@/Components/albums/UploadProgress.vue';
import { ref, computed } from 'vue';
import { useAlbum } from '@/composables/albums/useAlbum';
import type { Album, AlbumImage } from '@/types/album';
import type { User } from '@/types/index';

const props = defineProps<{
    album: Album;
    album_display_settings: NonNullable<User['album_display_settings']>;
    auth: {
        user: User;
    };
}>();

const {
    uploading,
    uploadQueue,
    completedCount,
    errorCount,
    pendingCount,
    overallProgress,
    showConfirmation,
    confirmationTitle,
    confirmationMessage,
    handleFileUpload,
    retryUpload,
    removeFromQueue,
    clearCompletedUploads,
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