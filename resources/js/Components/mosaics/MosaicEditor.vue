<template>
    <div class="mosaic-editor">
        <!-- Column Width Controls -->
        <div class="mb-6 space-y-4">
            <div class="space-y-3">
                <h3 class="text-center text-lg font-medium text-gray-900">
                    Column Widths
                </h3>

                <!-- Visual Columns with Centered Percentages -->
                <div class="relative">
                    <div
                        class="flex h-16 overflow-hidden rounded-lg border-2 border-gray-300 bg-white"
                    >
                        <div
                            v-for="(percentage, index) in columnPercentages"
                            :key="index"
                            class="relative flex flex-col items-center justify-center border-r border-gray-300 transition-all duration-200 last:border-r-0"
                            :style="{ width: percentage + '%' }"
                            :class="[
                                index % 2 === 0
                                    ? 'bg-gradient-to-br from-blue-50 to-blue-100'
                                    : 'bg-gradient-to-br from-gray-50 to-gray-100',
                            ]"
                        >
                            <span class="text-lg font-bold text-gray-800"
                                >{{ Math.round(percentage) }}%</span
                            >
                            <span class="text-xs text-gray-600"
                                >Column {{ index + 1 }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Adjustable Width Controls -->
                <div v-if="columnCount > 1" class="space-y-4">
                    <div
                        v-for="(slider, index) in columnCount - 1"
                        :key="index"
                        class="relative"
                    >
                        <!-- Interactive Width Adjuster -->
                        <div
                            class="flex items-center space-x-3 rounded-lg bg-gray-50 p-3"
                        >
                            <div class="flex-1">
                                <div
                                    class="mb-2 flex items-center justify-between"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-700"
                                    >
                                        Adjust between columns
                                        {{ index + 1 }} and {{ index + 2 }}
                                    </span>
                                    <button
                                        @click="resetColumnWidths"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-500"
                                    >
                                        Reset to equal
                                    </button>
                                </div>
                                <input
                                    type="range"
                                    :value="getColumnSplit(index)"
                                    @input="updateColumnSplit(index, $event)"
                                    min="10"
                                    max="90"
                                    step="1"
                                    class="slider h-3 w-full cursor-pointer appearance-none rounded-lg bg-gradient-to-r from-blue-200 via-gray-200 to-blue-200"
                                />
                                <div
                                    class="mt-1 flex justify-between text-xs text-gray-500"
                                >
                                    <span
                                        >{{
                                            Math.round(
                                                columnPercentages[index],
                                            )
                                        }}%</span
                                    >
                                    <span
                                        >{{
                                            Math.round(
                                                columnPercentages[index + 1],
                                            )
                                        }}%</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Item Height Control -->
        <div class="mb-6 rounded-lg bg-gray-50 p-4">
            <div class="mb-3 flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700"
                    >Global Item Height</label
                >
                <span class="text-sm text-gray-500"
                    >{{ itemHeight }}% of base size</span
                >
            </div>
            <input
                type="range"
                v-model="itemHeight"
                min="50"
                max="300"
                step="5"
                class="slider h-3 w-full cursor-pointer appearance-none rounded-lg bg-gradient-to-r from-green-200 via-yellow-200 to-red-200"
                @input="updateItemHeights"
            />
            <div class="mt-1 flex justify-between text-xs text-gray-500">
                <span>50% (100px)</span>
                <span>100% (200px)</span>
                <span>300% (600px)</span>
            </div>
        </div>

        <!-- Mosaic Grid -->
        <div
            class="grid gap-4"
            :style="{ gridTemplateColumns: gridTemplateColumns }"
        >
            <div
                v-for="columnIndex in columnCount"
                :key="columnIndex"
                class="mosaic-column"
                @dragover.prevent
                @drop.prevent="handleDrop($event, columnIndex - 1)"
            >
                <div
                    v-for="item in itemsInColumn(columnIndex - 1)"
                    :key="item.id"
                    class="mosaic-item group relative mb-4"
                    draggable="true"
                    @dragstart="handleDragStart($event, item)"
                    @click="openItemEditor(item)"
                    :style="{
                        height: `${(item.properties?.height || itemHeight) * 2}px`,
                        transition: 'height 0.3s ease-in-out',
                    }"
                >
                    <!-- Item Content -->
                    <div
                        class="h-full w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md"
                    >
                        <!-- Album Item -->
                        <template v-if="item.type === 'album'">
                            <img
                                :src="
                                    item.properties?.selected_image?.path ||
                                    item.properties?.album?.cover_image_path ||
                                    '/placeholder.jpg'
                                "
                                :alt="
                                    item.properties?.album?.title ||
                                    'Album image'
                                "
                                class="h-full w-full object-cover"
                                :style="getImageStyle(item)"
                            />
                        </template>

                        <!-- Media Item -->
                        <template v-else-if="item.type === 'media'">
                            <img
                                v-if="item.properties?.media?.type === 'image'"
                                :src="item.properties?.media?.path || ''"
                                :alt="item.properties?.text?.content || ''"
                                class="h-full w-full object-cover"
                                :style="getImageStyle(item)"
                            />
                            <video
                                v-else
                                :src="item.properties?.media?.path || ''"
                                class="h-full w-full object-cover"
                                controls
                            />
                        </template>

                        <!-- Color Item -->
                        <template v-else-if="item.type === 'color'">
                            <div
                                class="flex h-full w-full items-center justify-center"
                                :style="{
                                    backgroundColor:
                                        item.properties?.color || '#ffffff',
                                }"
                            >
                                <span
                                    v-if="item.properties?.text?.enabled"
                                    class="text-lg font-medium"
                                    :style="{
                                        color:
                                            item.properties?.text?.color ||
                                            '#000000',
                                    }"
                                >
                                    {{ item.properties?.text?.content }}
                                </span>
                            </div>
                        </template>

                        <!-- Text Overlay -->
                        <div
                            v-if="
                                item.properties?.text?.enabled &&
                                item.type !== 'color'
                            "
                            class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40 p-4"
                            :style="{
                                color:
                                    item.properties?.text?.color || '#ffffff',
                            }"
                        >
                            <p class="text-center font-medium">
                                {{ item.properties?.text?.content }}
                            </p>
                        </div>
                    </div>

                    <!-- Item Controls -->
                    <div
                        class="absolute right-2 top-2 space-x-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                    >
                        <button
                            @click.stop="openItemEditor(item)"
                            class="rounded-full bg-secondary p-1.5 text-black shadow-lg transition-colors hover:brightness-90"
                            title="Edit item"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </button>
                        <button
                            @click.stop="deleteItem(item.id)"
                            class="rounded-full bg-red-600 p-1.5 text-white shadow-lg transition-colors hover:bg-red-700"
                            title="Delete item"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Add Item Button -->
                <button
                    @click="openAddItemModal(columnIndex - 1)"
                    class="group w-full rounded-lg border-2 border-dashed border-gray-300 p-6 text-gray-500 transition-all duration-200 hover:border-secondary hover:bg-secondary hover:bg-opacity-10 hover:text-secondary"
                >
                    <div class="flex flex-col items-center justify-center">
                        <svg
                            class="mb-2 h-8 w-8 transition-transform duration-200 group-hover:scale-110"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span class="font-medium">Add Item</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Item Editor Modal -->
        <MosaicItemEditor
            :show="showItemEditor"
            :is-editing="!!editingItem"
            :item="
                editingItem || {
                    id: '',
                    type: 'media',
                    column_index: selectedColumn,
                    order: itemsInColumn(selectedColumn).length,
                    properties: { height: itemHeight },
                }
            "
            :albums="albums"
            :mosaicId="mosaicId"
            @update:modelValue="showItemEditor = $event"
            @close="closeItemEditor"
            @save="saveItem"
            @delete="deleteItem"
        />
    </div>
</template>

<script setup lang="ts">
import MosaicItemEditor from '@/Components/mosaics/MosaicItemEditor.vue';
import type { Album } from '@/types/album';
import type { Mosaic, MosaicItem } from '@/types/mosaic';
import { computed, ref } from 'vue';

interface MosaicItemWithImage extends MosaicItem {
    image_id?: string;
    path?: string;
}

const props = defineProps<{
    mosaic: Mosaic;
    albums: Album[];
    mosaicId?: string;
}>();

const emit = defineEmits<{
    (e: 'update', mosaic: Mosaic): void;
}>();

const columnCount = ref(props.mosaic.columns || 3);
const columnPercentages = ref(
    getInitialColumnPercentages(props.mosaic.columns || 3),
);
const itemHeight = ref(100);
const showItemEditor = ref(false);
const editingItem = ref<MosaicItemWithImage | null>(null);
const selectedColumn = ref(0);
const draggedItem = ref<MosaicItemWithImage | null>(null);

// Helper function to get initial column percentages
function getInitialColumnPercentages(columns: number): number[] {
    const percentage = 100 / columns;
    return Array(columns).fill(percentage);
}

// Computed
const itemsInColumn = (columnIndex: number) => {
    return props.mosaic.items
        .filter((item) => item.column_index === columnIndex)
        .sort((a, b) => a.order - b.order);
};

const gridTemplateColumns = computed(() => {
    return columnPercentages.value
        .map((percentage) => `${percentage}%`)
        .join(' ');
});

// Methods
const updateColumnPercentage = (index: number, event: Event) => {
    const newValue = parseInt((event.target as HTMLInputElement).value);
    const newPercentages = [...columnPercentages.value];
    newPercentages[index] = newValue;
    columnPercentages.value = newPercentages;
};

const handleDragStart = (event: DragEvent, item: MosaicItemWithImage) => {
    if (!event.dataTransfer) return;
    draggedItem.value = item;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', item.id);
};

const handleDrop = (event: DragEvent, columnIndex: number) => {
    event.preventDefault();
    if (!draggedItem.value) return;

    const items = [...props.mosaic.items];
    const itemIndex = items.findIndex(
        (item) => item.id === draggedItem.value?.id,
    );

    if (itemIndex !== -1) {
        const item = items[itemIndex];
        const newOrder = items.filter(
            (i) => i.column_index === columnIndex,
        ).length;

        items[itemIndex] = {
            ...item,
            column_index: columnIndex,
            order: newOrder,
        };

        emit('update', {
            ...props.mosaic,
            items,
        });
    }

    draggedItem.value = null;
};

const openItemEditor = (item: MosaicItemWithImage) => {
    editingItem.value = { ...item };
    showItemEditor.value = true;
};

const closeItemEditor = () => {
    editingItem.value = null;
    showItemEditor.value = false;
};

const openAddItemModal = (columnIndex: number) => {
    selectedColumn.value = columnIndex;
    editingItem.value = null;
    showItemEditor.value = true;
};

const saveItem = (item: MosaicItemWithImage) => {
    const items = [...props.mosaic.items];
    const itemIndex = items.findIndex((i) => i.id === item.id);

    if (itemIndex !== -1) {
        items[itemIndex] = item;
    } else {
        items.push(item);
    }

    emit('update', {
        ...props.mosaic,
        items,
    });

    closeItemEditor();
};

const deleteItem = (itemId: string) => {
    const items = props.mosaic.items.filter((item) => item.id !== itemId);
    emit('update', {
        ...props.mosaic,
        items,
    });
    closeItemEditor();
};

const resetColumnWidths = () => {
    columnPercentages.value = getInitialColumnPercentages(columnCount.value);
};

const getColumnSplit = (index: number) => {
    return columnPercentages.value[index];
};

const updateColumnSplit = (index: number, event: Event) => {
    const newValue = parseInt((event.target as HTMLInputElement).value);
    const newPercentages = [...columnPercentages.value];
    const totalAdjusted = newValue + columnPercentages.value[index + 1];

    // Distribute the change proportionally
    newPercentages[index] = newValue;
    newPercentages[index + 1] = 100 - newValue;

    // Normalize if we have more than 2 columns
    if (columnPercentages.value.length > 2) {
        const remaining = 100 - newValue - newPercentages[index + 1];
        const otherColumns = columnPercentages.value.length - 2;
        if (otherColumns > 0) {
            const distributedValue = remaining / otherColumns;
            for (let i = 0; i < newPercentages.length; i++) {
                if (i !== index && i !== index + 1) {
                    newPercentages[i] = distributedValue;
                }
            }
        }
    }

    columnPercentages.value = newPercentages;
};

const updateItemHeights = () => {
    // Emit update to parent component if needed
    emit('update', {
        ...props.mosaic,
        items: props.mosaic.items.map((item) => ({
            ...item,
            properties: {
                ...item.properties,
                height: itemHeight.value,
            },
        })),
    });
};

const getImageStyle = (item: MosaicItemWithImage) => {
    const mediaProps = item.properties?.media;
    if (!mediaProps) return {};

    const scale = mediaProps.scale || 1;
    const position = mediaProps.position || 'center center';

    return {
        transform: `scale(${scale})`,
        objectPosition: position,
        transformOrigin: 'center center',
    };
};
</script>

<style scoped>
.mosaic-editor {
    @apply p-4;
}

.mosaic-column {
    @apply min-h-[200px] p-2;
}

.mosaic-item {
    @apply cursor-move;
}

.mosaic-item img {
    @apply transition-transform duration-200;
}

.mosaic-item:hover img {
    @apply scale-[1.02] transform;
}

/* Custom Slider Styles */
.slider {
    @apply cursor-pointer appearance-none rounded-lg bg-gray-200;
}

.slider::-webkit-slider-thumb {
    @apply h-6 w-6 cursor-pointer appearance-none rounded-full shadow-lg;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: 2px solid white;
}

.slider::-moz-range-thumb {
    @apply h-6 w-6 cursor-pointer rounded-full border-2 border-white shadow-lg;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: 2px solid white;
}

.slider:focus {
    @apply outline-none ring-2 ring-blue-500 ring-opacity-50;
}

.slider:hover::-webkit-slider-thumb {
    @apply scale-110;
    background: linear-gradient(135deg, #2563eb, #1e40af);
}

.slider:hover::-moz-range-thumb {
    @apply scale-110;
    background: linear-gradient(135deg, #2563eb, #1e40af);
}

/* Transitions for smooth interactions */
.group {
    transition: all 0.2s ease-in-out;
}

/* Enhanced hover effects */
.mosaic-item:hover {
    @apply scale-[1.02] transform;
}

/* Visual feedback for drag and drop */
.mosaic-column:hover {
    @apply rounded-lg bg-gray-50;
}

/* Column percentage display animation */
.relative > div {
    transition: width 0.3s ease-in-out;
}
</style>
