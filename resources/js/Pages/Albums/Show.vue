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
                        <DraggableImage
                            v-for="image in images"
                            :key="image.id"
                            :image="image"
                            :is-drop-target="isDragOver && draggedImage?.id !== image.id"
                            @drag-start="handleDragStart"
                            @drag-end="handleDragEnd"
                            @update-position="handleDragMove"
                        />
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
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageModal from '@/Components/ImageModal.vue';
import DraggableImage from '@/Components/DraggableImage.vue';
import { ref, onMounted } from 'vue';

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
const dropTargetIndex = ref(-1);

const openModal = (image) => {
    selectedImage.value = image;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedImage.value = null;
};

const handleDragStart = (image) => {
    isDragging.value = true;
    draggedImage.value = image;
};

const handleDragEnd = async () => {
    if (!isDragging.value || !draggedImage.value) return;

    isDragging.value = false;
    const draggedIdx = images.value.findIndex(img => img.id === draggedImage.value.id);
    const targetIdx = dropTargetIndex.value;

    if (targetIdx !== -1 && targetIdx !== draggedIdx) {
        const newImages = [...images.value];
        const [movedImage] = newImages.splice(draggedIdx, 1);
        newImages.splice(targetIdx, 0, movedImage);
        images.value = newImages;

        try {
            await router.post(route('album-images.reorder'), {
                image_id: draggedImage.value.id,
                new_order: targetIdx
            });
        } catch (error) {
            console.error('Failed to reorder images:', error);
            images.value = props.images;
        }
    }

    draggedImage.value = null;
    isDragOver.value = false;
    dropTargetIndex.value = -1;
};

const handleDragMove = ({ image, position }) => {
    const imageElements = document.querySelectorAll('.aspect-square');
    const draggedRect = imageElements[images.value.findIndex(img => img.id === draggedImage.value?.id)]?.getBoundingClientRect();
    
    if (!draggedRect) return;

    const dragCenter = {
        x: draggedRect.left + position.x + draggedRect.width / 2,
        y: draggedRect.top + position.y + draggedRect.height / 2
    };

    let closestDistance = Infinity;
    let closestIndex = -1;

    imageElements.forEach((el, index) => {
        if (images.value[index].id === draggedImage.value?.id) return;

        const rect = el.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        
        const distance = Math.sqrt(
            Math.pow(dragCenter.x - centerX, 2) + 
            Math.pow(dragCenter.y - centerY, 2)
        );

        if (distance < closestDistance) {
            closestDistance = distance;
            closestIndex = index;
        }
    });

    if (closestDistance < 100) { // Threshold for considering it "close enough"
        isDragOver.value = true;
        dropTargetIndex.value = closestIndex;
    } else {
        isDragOver.value = false;
        dropTargetIndex.value = -1;
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

.translate-x-full {
    transform: translateX(100%);
}

.-translate-x-full {
    transform: translateX(-100%);
}

.translate-y-full {
    transform: translateY(100%);
}

.-translate-y-full {
    transform: translateY(-100%);
}
</style>