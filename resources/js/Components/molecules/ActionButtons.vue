<template>
    <div class="absolute inset-0 flex items-center justify-center gap-4 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
        <Button
            v-for="action in actions"
            :key="action.label"
            @click.prevent="action.handler"
            :variant="action.variant || 'primary'"
            size="sm"
            icon-only
            :title="action.label"
        >
            <Icon :name="action.icon" size="sm" />
        </Button>
    </div>
</template>

<script setup lang="ts">
import Button from '@/Components/Base/Button.vue';
import Icon from '@/Components/Base/Icon.vue';

interface Action {
    label: string;
    handler: () => void;
    icon: 'arrow-left' | 'video' | 'play-circle' | 'play-circle-outline' | 'trash' | 'x' | 'exclamation-triangle' | 'edit';
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
}

interface Props {
    actions: Action[];
}

defineProps<Props>();
</script>
