<template>
    <draggable 
        v-model="localItems" 
        item-key="id"
        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4"
        ghost-class="ghost-item"
        chosen-class="chosen-item"
        drag-class="drag-item"
        :animation="200"
        @end="handleReorder"
    >
        <template #item="{ element: item }">
            <div class="relative group">
                <AlbumItem
                    :item="item"
                    @click="handleItemClick"
                    @delete="handleItemDelete"
                />
            </div>
        </template>
    </draggable>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
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