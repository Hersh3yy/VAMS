<template>
    <Head :title="mosaic.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="header-container">
                <h2 class="header-title">
                    {{ mosaic.title }}
                </h2>
                <div class="action-buttons">
                    <button @click="saveMosaic" class="save-button">
                        Save Layout
                    </button>
                    <button @click="showAlbumSelector = true" class="add-images-button">
                        Add Images
                    </button>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="editor-container">
                <!-- Grid Layout Editor -->
                <div class="editor-card">
                    <div class="editor-grid">
                        <div v-for="item in items" 
                             :key="item.id"
                             class="grid-item"
                             :style="getItemStyle(item)"
                             v-draggable="draggableOptions"
                             v-resizable="resizableOptions">
                            <img :src="item.image_path" 
                                 :alt="item.title"
                                 class="item-image">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Album Selector Modal -->
        <Modal :show="showAlbumSelector" @close="showAlbumSelector = false">
            <div class="modal-content">
                <h3 class="modal-title">Select Images</h3>
                <div class="image-grid">
                    <!-- Album images grid here -->
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    mosaic: Object,
    items: Array
});

const items = ref(props.items);
const showAlbumSelector = ref(false);

const getItemStyle = (item) => {
    const position = item.desktop_position;
    return {
        left: `${position.x}%`,
        top: `${position.y}%`,
        width: `${position.width}%`,
        height: `${position.height}%`,
    };
};

const saveMosaic = async () => {
    try {
        await router.put(route('mosaics.update', mosaic.id), {
            items: items.value
        });
    } catch (error) {
        console.error('Failed to save mosaic:', error);
    }
};

// Add draggable and resizable directives/logic here
</script>

<style scoped>
.header-container {
    @apply flex justify-between items-center;
}

.header-title {
    @apply font-semibold text-xl text-gray-800 leading-tight;
}

.action-buttons {
    @apply flex gap-4;
}

.save-button {
    @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded;
}

.add-images-button {
    @apply bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded;
}

.content-wrapper {
    @apply py-12;
}

.editor-container {
    @apply max-w-7xl mx-auto sm:px-6 lg:px-8;
}

.editor-card {
    @apply bg-white p-6 rounded-lg shadow;
}

.editor-grid {
    @apply grid grid-cols-12 gap-4 min-h-[600px] relative;
}

.grid-item {
    @apply absolute;
}

.item-image {
    @apply w-full h-full object-cover;
}

.modal-content {
    @apply p-6;
}

.modal-title {
    @apply text-lg font-medium mb-4;
}

.image-grid {
    @apply grid grid-cols-3 gap-4;
}
</style> 