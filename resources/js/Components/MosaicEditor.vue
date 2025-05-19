<template>
    <div class="mosaic-editor">
        <!-- Orientation Tabs -->
        <div class="flex gap-2 mb-4">
            <button 
                @click="orientation = 'landscape'"
                class="px-4 py-2 rounded-lg"
                :class="orientation === 'landscape' ? 'bg-blue-500 text-white' : 'bg-gray-100'"
            >
                Landscape
            </button>
            <button 
                @click="orientation = 'portrait'"
                class="px-4 py-2 rounded-lg"
                :class="orientation === 'portrait' ? 'bg-blue-500 text-white' : 'bg-gray-100'"
            >
                Portrait
            </button>
        </div>

        <!-- Editor Area -->
        <div 
            class="mosaic-container relative bg-white rounded-lg shadow-lg"
            :class="orientation"
            :style="containerStyle"
        >
            <template v-if="items.length">
                <MosaicTile
                    v-for="item in items"
                    :key="item.id"
                    :type="item.type"
                    :position="item.position"
                    :image-src="item.image?.src"
                    :image-alt="item.image?.alt"
                    :image-position="item.image?.position"
                    :image-overlay="item.image?.overlay"
                    :split-direction="item.split_direction"
                    :split-ratio="item.split_ratio"
                    @split="handleSplit(item)"
                    @image="handleImage(item)"
                    @delete="handleDelete(item)"
                    @update:position="updatePosition(item, $event)"
                    @update:image-position="updateImagePosition(item, $event)"
                    @update:split-ratio="updateSplitRatio(item, $event)"
                />
            </template>
            <div v-else class="empty-state">
                <button @click="createInitialTile" class="create-button">
                    Create First Tile
                </button>
            </div>
        </div>

        <!-- Split Modal -->
        <MosaicSplitModal
            v-model="showSplitModal"
            @split="handleSplitConfirm"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { v4 as uuidv4 } from 'uuid';
import MosaicTile from './MosaicTile.vue';
import MosaicSplitModal from './MosaicSplitModal.vue';

interface Position {
    x: number;
    y: number;
    width: number;
    height: number;
}

interface ImageData {
    src: string;
    alt: string;
    position: {
        x: number;
        y: number;
        scale: number;
    };
    overlay?: string;
}

interface MosaicItem {
    id: string;
    type: 'container' | 'image';
    position: Position;
    image?: ImageData;
    split_direction?: 'horizontal' | 'vertical' | null;
    split_ratio?: number;
    parent_id?: string | null;
}

const props = defineProps<{
    modelValue: MosaicItem[];
}>();

const emit = defineEmits<{
    'update:modelValue': [items: MosaicItem[]];
    'save': [items: MosaicItem[]];
    'image-select': [itemId: string];
}>();

const orientation = ref<'landscape' | 'portrait'>('landscape');
const showSplitModal = ref(false);
const selectedItemId = ref<string | null>(null);

const items = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const containerStyle = computed(() => ({
    aspectRatio: orientation.value === 'landscape' ? '16/9' : '9/16'
}));

// Item Management
const createInitialTile = () => {
    const newItem: MosaicItem = {
        id: uuidv4(),
        type: 'container',
        position: { x: 0, y: 0, width: 100, height: 100 },
        image: {
            src: '',
            alt: '',
            position: { x: 0, y: 0, scale: 1 }
        }
    };
    items.value = [newItem];
    emit('update:modelValue', items.value);
};

const handleSplit = (item: MosaicItem) => {
    if (item.type === 'image') {
        // Convert image to container before splitting
        const index = items.value.findIndex(i => i.id === item.id);
        if (index !== -1) {
            items.value[index] = {
                ...items.value[index],
                type: 'container',
                image: undefined
            };
            emit('update:modelValue', items.value);
        }
    }
    selectedItemId.value = item.id;
    showSplitModal.value = true;
};

const handleSplitConfirm = (direction: 'horizontal' | 'vertical') => {
    if (!selectedItemId.value) return;
    
    const item = items.value.find(i => i.id === selectedItemId.value);
    if (!item) return;

    const position = item.position;
    const ratio = 0.5;

    const newItems: MosaicItem[] = [
        {
            id: uuidv4(),
            type: 'container',
            parent_id: item.id,
            position: {
                x: position.x,
                y: position.y,
                width: direction === 'vertical' ? position.width * ratio : position.width,
                height: direction === 'horizontal' ? position.height * ratio : position.height
            }
        },
        {
            id: uuidv4(),
            type: 'container',
            parent_id: item.id,
            position: {
                x: direction === 'vertical' ? position.x + (position.width * ratio) : position.x,
                y: direction === 'horizontal' ? position.y + (position.height * ratio) : position.y,
                width: direction === 'vertical' ? position.width * (1 - ratio) : position.width,
                height: direction === 'horizontal' ? position.height * (1 - ratio) : position.height
            }
        }
    ];

    item.split_direction = direction;
    item.split_ratio = ratio;

    items.value = [...items.value, ...newItems];
    showSplitModal.value = false;
};

const handleImage = (item: MosaicItem) => {
    if (item.type === 'container') {
        // Convert container to image tile
        const index = items.value.findIndex(i => i.id === item.id);
        if (index !== -1) {
            items.value[index] = {
                ...items.value[index],
                type: 'image',
                image: {
                    src: '',
                    alt: '',
                    position: { x: 0, y: 0, scale: 1 }
                }
            };
            emit('update:modelValue', items.value);
        }
    }
    emit('image-select', item.id);
};

const handleDelete = (item: MosaicItem) => {
    // Remove item and its children
    const itemsToRemove = new Set([item.id]);
    items.value.forEach(i => {
        if (i.parent_id && itemsToRemove.has(i.parent_id)) {
            itemsToRemove.add(i.id);
        }
    });
    
    items.value = items.value.filter(i => !itemsToRemove.has(i.id));
};

const updatePosition = (item: MosaicItem, position: Position) => {
    const index = items.value.findIndex(i => i.id === item.id);
    if (index === -1) return;

    items.value[index] = { ...items.value[index], position };
};

const updateImagePosition = (item: MosaicItem, position: { x: number; y: number; scale: number }) => {
    const index = items.value.findIndex(i => i.id === item.id);
    if (index === -1 || !items.value[index].image) return;

    items.value[index] = {
        ...items.value[index],
        image: {
            ...items.value[index].image!,
            position
        }
    };
};

const updateSplitRatio = (item: MosaicItem, ratio: number) => {
    const index = items.value.findIndex(i => i.id === item.id);
    if (index === -1) return;

    const children = items.value.filter(i => i.parent_id === item.id);
    if (children.length !== 2) return;

    const direction = items.value[index].split_direction;
    if (!direction) return;

    // Update parent's split ratio
    items.value[index] = { ...items.value[index], split_ratio: ratio };

    // Update children positions
    const position = items.value[index].position;
    children.forEach((child, i) => {
        const childIndex = items.value.findIndex(item => item.id === child.id);
        if (childIndex === -1) return;

        items.value[childIndex] = {
            ...items.value[childIndex],
            position: {
                x: direction === 'vertical' ? position.x + (i === 1 ? position.width * ratio : 0) : position.x,
                y: direction === 'horizontal' ? position.y + (i === 1 ? position.height * ratio : 0) : position.y,
                width: direction === 'vertical' ? position.width * (i === 1 ? 1 - ratio : ratio) : position.width,
                height: direction === 'horizontal' ? position.height * (i === 1 ? 1 - ratio : ratio) : position.height
            }
        };
    });
};
</script>

<style scoped>
.mosaic-editor {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.mosaic-container {
    width: 100%;
    height: 0;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    position: relative;
    background: white;
}

.mosaic-container.portrait {
    padding-bottom: 177.78%; /* 9:16 aspect ratio */
}

.empty-state {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.create-button {
    padding: 0.75rem 1.5rem;
    background-color: var(--primary-color);
    color: white;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s;
}

.create-button:hover {
    filter: brightness(110%);
}
</style> 