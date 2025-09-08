<template>
    <BaseButton @click="$emit('click')" :variant="isActive ? 'primary' : 'secondary'" :size="size">
        {{ label }}
    </BaseButton>
</template>

<script setup lang="ts">
import BaseButton from '@/Components/Base/Button.vue';

interface Props {
    label: string;
    isActive?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

defineProps<Props>();
defineEmits<{
    click: [];
}>();
</script>
