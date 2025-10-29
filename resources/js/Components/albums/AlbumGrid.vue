<template>
    <div ref="gridContainer" class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
        <div
            v-for="(item, index) in localItems"
            :key="item.id"
            :data-id="item.id"
            :data-index="index"
            class="group relative"
        >
            <AlbumItem :item="item" @click="handleItemClick" @delete="handleItemDelete" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
// @ts-ignore
import type { AlbumImage } from '@/types/album';
import Sortable from 'sortablejs';
import AlbumItem from './AlbumItem.vue';

const props = defineProps<{
    items: AlbumImage[];
}>();

const emit = defineEmits<{
    (e: 'item-click', item: AlbumImage): void;
    (e: 'item-delete', item: AlbumImage): void;
    (e: 'reorder', fromIndex: number, toIndex: number): void;
}>();

const gridContainer = ref<HTMLElement | null>(null);
const localItems = ref([...props.items]);
let sortableInstance: any = null;

// Watch for prop changes - but be careful not to reset during drag operations
let isDragging = false;

watch(
    () => props.items,
    (newItems, oldItems) => {
        // Only update if:
        // 1. Not currently dragging
        // 2. The items array structure actually changed (not just a reorder we already handled)
        if (!isDragging && newItems.length === oldItems?.length) {
            // Check if items changed beyond just position (e.g., new items added, items removed)
            const idsChanged = newItems.some((item, index) => {
                return !oldItems?.[index] || item.id !== oldItems[index].id;
            });
            
            if (idsChanged) {
                localItems.value = [...newItems];
            }
        } else if (!isDragging) {
            // Items were added/removed, always sync
            localItems.value = [...newItems];
        }
    },
    { deep: true }
);

const handleItemClick = (item: AlbumImage) => {
    emit('item-click', item);
};

const handleItemDelete = (item: AlbumImage) => {
    emit('item-delete', item);
};

const handleStart = () => {
    isDragging = true;
};

const handleEnd = (evt: any) => {
    isDragging = false;
    
    if (evt.oldIndex !== evt.newIndex && evt.oldIndex !== undefined && evt.newIndex !== undefined) {
        // Update local state IMMEDIATELY to prevent snap-back
        const movedItem = localItems.value.splice(evt.oldIndex, 1)[0];
        localItems.value.splice(evt.newIndex, 0, movedItem);
        
        // Then emit to trigger the API call
        emit('reorder', evt.oldIndex, evt.newIndex);
    }
};

onMounted(() => {
    if (gridContainer.value) {
        sortableInstance = Sortable.create(gridContainer.value, {
            animation: 150,
            ghostClass: 'ghost-item',
            chosenClass: 'chosen-item',
            dragClass: 'drag-item',
            onStart: handleStart,
            onEnd: handleEnd
        });
    }
});

onUnmounted(() => {
    if (sortableInstance) {
        sortableInstance.destroy();
    }
});
</script>

<style scoped>
.ghost-item {
    @apply border-2 border-dashed border-blue-300 bg-blue-100 opacity-50;
}

.chosen-item {
    @apply scale-105 transform ring-2 ring-blue-500;
}

.drag-item {
    @apply rotate-3 transform shadow-lg;
}

.group {
    @apply transition-all duration-200 ease-in-out;
}

.group:hover {
    @apply -translate-y-1 transform shadow-lg;
}
</style>
