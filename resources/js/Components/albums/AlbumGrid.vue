<template>
    <div 
        ref="gridContainer"
        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4"
    >
        <div 
            v-for="(item, index) in localItems" 
            :key="item.id"
            :data-id="item.id"
            :data-index="index"
            class="relative group"
        >
            <AlbumItem
                :item="item"
                @click="handleItemClick"
                @delete="handleItemDelete"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
// @ts-ignore
import Sortable from 'sortablejs';
import AlbumItem from './AlbumItem.vue';
import type { AlbumImage } from '@/types/album';

interface SortableEvent {
    oldIndex?: number;
    newIndex?: number;
}

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

onMounted(() => {
    if (gridContainer.value) {
        sortableInstance = Sortable.create(gridContainer.value, {
            animation: 150,
            ghostClass: 'ghost-item',
            chosenClass: 'chosen-item',
            dragClass: 'drag-item',
            onStart: (evt: SortableEvent) => {
                console.log('Drag start - oldIndex:', evt.oldIndex);
            },
            onEnd: (evt: SortableEvent) => {
                console.log('Drag end - oldIndex:', evt.oldIndex, 'newIndex:', evt.newIndex);
                
                if (evt.oldIndex !== undefined && 
                    evt.newIndex !== undefined && 
                    evt.oldIndex !== evt.newIndex) {
                    
                    // Update local items array
                    const movedItem = localItems.value[evt.oldIndex];
                    localItems.value.splice(evt.oldIndex, 1);
                    localItems.value.splice(evt.newIndex, 0, movedItem);
                    
                    console.log('Emitting reorder:', evt.oldIndex, evt.newIndex);
                    emit('reorder', evt.oldIndex, evt.newIndex);
                }
            }
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