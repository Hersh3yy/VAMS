<template>
    <Head :title="mosaic.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ mosaic.title }}
                </h2>
                <div class="flex gap-4">
                    <button 
                        @click="saveMosaic" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                        :class="{ 'opacity-50': !hasChanges }"
                        :disabled="!hasChanges"
                    >
                        {{ hasChanges ? 'Save Changes' : 'Saved' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <MosaicEditor
                    :mosaic="{
                        ...mosaic,
                        items: mosaicItems
                    }"
                    :onUpdate="handleMosaicUpdate"
                />
            </div>
        </div>

        <!-- Image Selection Modal -->
        <Modal v-model="showImageModal">
            <template #title>Select Image</template>
            
            <div class="space-y-6">
                <div v-for="album in props.albums" :key="album.id" class="space-y-2">
                    <h3 class="font-medium text-gray-900">{{ album.title }} ({{ album.images.length }} images)</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div 
                            v-for="image in album.images" 
                            :key="image.id"
                            class="relative aspect-square cursor-pointer group"
                            @click="selectImage(image)"
                        >
                            <img 
                                :src="getImageUrl(image)"
                                :alt="image.title"
                                class="w-full h-full object-cover rounded-lg"
                            />
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-lg">
                                <span class="text-white text-sm">Select</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MosaicEditor from '@/Components/MosaicEditor.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';

interface MosaicItem {
    id: string;
    type: 'image' | 'text' | 'album';
    column_index: number;
    order: number;
    content?: string | string[];
    album_id?: string;
    album?: any;
    images?: any[];
    properties?: {
        src?: string;
        alt?: string;
        caption?: string;
        size?: 'fill' | 'cover' | 'contain';
        text?: {
            content: string;
            color: string;
        };
        link?: {
            url: string;
        };
    };
}

interface Mosaic {
    id: string;
    title: string;
    columns: number;
    items: MosaicItem[];
}

const props = defineProps<{
    mosaic: Mosaic;
    albums: {
        id: string;
        title: string;
        images: {
            id: string;
            title: string;
            thumbnail_url: string;
            url: string;
        }[];
    }[];
}>();

const mosaicItems = ref<MosaicItem[]>(props.mosaic.items || []);
const showImageModal = ref(false);
const selectedItemId = ref<string | null>(null);
const hasChanges = ref(false);

const availableImages = computed(() => {
    return props.albums.flatMap(album => album.images);
});

const handleMosaicUpdate = (updatedMosaic: { columns: number; items: MosaicItem[] }) => {
    mosaicItems.value = updatedMosaic.items;
    hasChanges.value = true;
};

const openImageSelector = (itemId: string) => {
    selectedItemId.value = itemId;
    showImageModal.value = true;
};

const selectImage = (image: any) => {
    if (!selectedItemId.value) return;
    
    const itemIndex = mosaicItems.value.findIndex(item => item.id === selectedItemId.value);
    if (itemIndex === -1) return;

    // Get the image URL
    const imageUrl = getImageUrl(image);
    
    mosaicItems.value[itemIndex] = {
        ...mosaicItems.value[itemIndex],
        type: 'image',
        properties: {
            src: imageUrl,
            alt: image.title || '',
            size: 'cover' as const
        }
    };

    hasChanges.value = true;
    showImageModal.value = false;
};

const getImageUrl = (image: any) => {
    // Try to get thumbnail URL from properties
    if (image.properties) {
        const props = typeof image.properties === 'string' 
            ? JSON.parse(image.properties) 
            : image.properties;
            
        if (props.thumbnail_url) {
            return props.thumbnail_url;
        }
    }
    
    // Fallback to regular path
    return image.path;
};

const saveMosaic = () => {
    axios.patch(`/mosaics/${props.mosaic.id}`, {
        items: mosaicItems.value
    }).then(() => {
        hasChanges.value = false;
    });
};

// Watch for changes
watch(mosaicItems, () => {
    hasChanges.value = true;
}, { deep: true });
</script>