<template>
    <BaseGrid
        :items="items"
        :settings="{ columns: 4, responsive: true }"
        :draggable="true"
        drag-type="sortable"
        @item-click="handleItemClick"
        @item-delete="handleItemDelete"
        @reorder="handleReorder"
    >
        <template #item="{ item, onClick, onDelete }">
            <AlbumItem
                :item="item"
                @click="onClick"
                @delete="onDelete"
            />
        </template>
    </BaseGrid>
</template>

<script setup lang="ts">
import BaseGrid from '@/Components/shared/BaseGrid.vue';
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

const handleItemClick = (item: AlbumImage) => {
    emit('item-click', item);
};

const handleItemDelete = (item: AlbumImage) => {
    emit('item-delete', item);
};

const handleReorder = (fromIndex: number | string, toIndex: number | string) => {
    emit('reorder', fromIndex as number, toIndex as number);
};
</script> 