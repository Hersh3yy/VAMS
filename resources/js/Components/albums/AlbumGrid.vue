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

// Watch for prop changes
watch(
    () => props.items,
    newItems => {
        localItems.value = [...newItems];
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
    // Track drag start
};

const handleEnd = (evt: any) => {
    if (evt.oldIndex !== evt.newIndex) {
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
