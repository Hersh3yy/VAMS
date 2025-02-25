<template>
    <Head :title="mosaic.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ mosaic.title }}
                </h2>
                <div class="flex gap-4">
                    <button @click="saveMosaic" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Save Layout
                    </button>
                    <button @click="showAlbumSelector = true" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Add Images
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Grid Layout Editor -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="grid grid-cols-12 gap-4 min-h-[600px] relative">
                        <div v-for="item in items" 
                             :key="item.id"
                             class="absolute"
                             :style="getItemStyle(item)"
                             v-draggable="draggableOptions"
                             v-resizable="resizableOptions">
                            <img :src="item.image_path" 
                                 :alt="item.title"
                                 class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Album Selector Modal -->
        <Modal :show="showAlbumSelector" @close="showAlbumSelector = false">
            <div class="p-6">
                <h3 class="text-lg font-medium mb-4">Select Images</h3>
                <div class="grid grid-cols-3 gap-4">
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