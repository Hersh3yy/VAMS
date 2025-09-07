<template>
    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-900">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-yellow-400">
                    {{ label }}
                </p>
                <p class="text-3xl font-bold text-secondary">
                    {{ value }}
                </p>
            </div>
            <div class="flex-shrink-0">
                <Icon :name="icon" size="lg" class="text-secondary" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Base/Icon.vue';

interface Props {
    label: string;
    value: number | string;
    icon: 'collection' | 'template' | 'photograph' | 'video';
}

defineProps<Props>();
</script>
