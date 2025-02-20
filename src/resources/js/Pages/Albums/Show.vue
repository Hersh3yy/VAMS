<template>

    <Head :title="album.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ album.title }}</h2>
                <label class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer">
                    Add Images
                    <input type="file" class="hidden" multiple accept="image/*" @change="handleFileUpload">
                </label>
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
                            <img :src="image.path" :alt="image.title"
                                class="object-cover w-full h-full transition-transform duration-200 group-hover:scale-105">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <ImageModal :show="showModal" :image="selectedImage" @close="closeModal" @update="handleImageUpdate" />
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageModal from '@/Components/ImageModal.vue';
import { ref, onMounted } from 'vue';

const props = defineProps({
    album: Object,
    images: Array
});

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedImage = ref(null);
const images = ref(props.images);
const uploading = ref(false);
const uploadProgress = ref(0);
const showModal = ref(false);
const selectedImage = ref(null);

const openModal = (image) => {
    selectedImage.value = image;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedImage.value = null;
};

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
</script>

<style scoped>
.group {
    @apply transition-all duration-200;
}

.group:hover {
    @apply ring-4 ring-blue-300;
}
</style>