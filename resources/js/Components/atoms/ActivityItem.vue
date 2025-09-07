<template>
    <div class="flex items-center justify-between py-3">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <div :class="iconBgClass" class="flex h-8 w-8 items-center justify-center rounded-full">
                    <Icon :name="iconName" size="sm" class="text-white" />
                </div>
            </div>
            <div class="min-w-0 flex-grow">
                <p class="text-sm text-gray-900 dark:text-yellow-200">
                    {{ activity.description }}
                </p>
                <p class="text-xs text-gray-500 dark:text-yellow-400">
                    {{ activity.created_at }}
                </p>
            </div>
        </div>
        <Badge :variant="badgeVariant" size="sm">
            {{ activity.type }}
        </Badge>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/Components/Base/Icon.vue';
import Badge from '@/Components/Base/Badge.vue';

interface Activity {
    id: number;
    type: 'create' | 'update' | 'delete';
    description: string;
    created_at: string;
}

interface Props {
    activity: Activity;
}

const props = defineProps<Props>();

const iconName = computed(() => {
    switch (props.activity.type) {
        case 'create':
            return 'collection';
        case 'update':
            return 'edit';
        case 'delete':
            return 'trash';
        default:
            return 'clock';
    }
});

const iconBgClass = computed(() => {
    switch (props.activity.type) {
        case 'create':
            return 'bg-green-500';
        case 'update':
            return 'bg-blue-500';
        case 'delete':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
    }
});

const badgeVariant = computed(() => {
    switch (props.activity.type) {
        case 'create':
            return 'success';
        case 'update':
            return 'info';
        case 'delete':
            return 'danger';
        default:
            return 'default';
    }
});
</script>
