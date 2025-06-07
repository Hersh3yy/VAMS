<template>
    <Draggable 
        v-model="localItems" 
        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4"
        :transition="200"
        item-key="id"
        @change="handleDragChange"
        @start="handleDragStart"
        @end="handleDragEnd"
    >
        <template v-slot:item="{ item }">
            <div class="relative group">
                <AlbumItem
                    :item="item"
                    @click="handleItemClick"
                    @delete="handleItemDelete"
                />
            </div>
        </template>
    </Draggable>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
// @ts-ignore
import Draggable from 'vue3-draggable';
import AlbumItem from './AlbumItem.vue';
import type { AlbumImage } from '@/types/album';

const props = defineProps<{
    items: AlbumImage[];
}>();

const emit = defineEmits<{
    (e: 'item-click', item: AlbumImage): void;
    (e: 'item-delete', item: AlbumImage): void;
    (e: 'reorder', fromIndex: number, toIndex: number): void;
}>();

const localItems = ref([...props.items]);

// Watch for prop changes
watch(() => props.items, (newItems) => {
    localItems.value = [...newItems];
}, { deep: true });

const handleItemClick = (item: AlbumImage) => {
    emit('item-click', item);
};

const handleItemDelete = (item: AlbumImage) => {
    emit('item-delete', item);
};

const handleReorder = (event: any) => {
    if (event.oldIndex !== event.newIndex) {
        emit('reorder', event.oldIndex, event.newIndex);
    }
};

const handleDragChange = (event: any) => {
    console.log('Drag change event:', event);
    if (event.moved) {
        const { oldIndex, newIndex } = event.moved;
        console.log('Emitting reorder:', oldIndex, newIndex);
        emit('reorder', oldIndex, newIndex);
    }
};

const handleDragStart = (event: any) => {
    console.log('Drag start:', event);
};

const handleDragEnd = (event: any) => {
    console.log('Drag end:', event);
    // Also emit reorder on end as a fallback
    if (event.oldIndex !== undefined && event.newIndex !== undefined && event.oldIndex !== event.newIndex) {
        console.log('Emitting reorder from end event:', event.oldIndex, event.newIndex);
        emit('reorder', event.oldIndex, event.newIndex);
    }
};
</script>

<style scoped>
.ghost-item {
    @apply opacity-50 bg-blue-100 border-2 border-blue-300 border-dashed;
}

.chosen-item {
    @apply ring-2 ring-blue-500 transform scale-105;
}

.drag-item {
    @apply transform rotate-3 shadow-lg;
}

.group {
    @apply transition-all duration-200 ease-in-out;
}

.group:hover {
    @apply transform -translate-y-1 shadow-lg;
}
</style> 