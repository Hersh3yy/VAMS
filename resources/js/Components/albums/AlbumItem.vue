<template>
    <AlbumItemCard :item="item" @click="$emit('click', $event)" @delete="$emit('delete', $event)" />
</template>

<script setup lang="ts">
import AlbumItemCard from '@/Components/Composite/AlbumItemCard.vue';
import type { AlbumImage } from '@/types/album';

const _props = defineProps<{
    item: AlbumImage;
}>();

const _emit = defineEmits<{
    (e: 'click', item: AlbumImage): void;
    (e: 'delete', item: AlbumImage): void;
}>();
</script>
