<template>
    <Link
        :href="entity.url"
        class="flex items-center space-x-4 rounded-lg p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
    >
        <div class="flex-shrink-0">
            <img
                v-if="entity.cover_image_path"
                :src="entity.cover_image_path"
                :alt="entity.title"
                class="h-12 w-12 rounded-lg object-cover"
            />
            <div
                v-else
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-200 dark:bg-gray-700"
            >
                <Icon
                    :name="entity.type === 'album' ? 'collection' : 'template'"
                    size="md"
                    class="text-gray-400"
                />
            </div>
        </div>
        <div class="min-w-0 flex-grow">
            <div class="flex items-center gap-2">
                <p class="truncate text-sm font-medium text-gray-900 dark:text-yellow-200">
                    {{ entity.title }}
                </p>
                <Badge
                    :variant="entity.type === 'album' ? 'info' : 'warning'"
                    size="sm"
                >
                    {{ entity.type }}
                </Badge>
            </div>
            <p class="text-xs text-gray-500 dark:text-yellow-400">
                {{ entity.items_count }} items · {{ entity.created_at }}
            </p>
        </div>
    </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Base/Icon.vue';
import Badge from '@/Components/Base/Badge.vue';

interface Entity {
    id: string | number;
    type: 'album' | 'mosaic';
    title: string;
    description?: string;
    cover_image_path?: string;
    items_count: number;
    created_at: string;
    url: string;
}

interface Props {
    entity: Entity;
}

defineProps<Props>();
</script>
