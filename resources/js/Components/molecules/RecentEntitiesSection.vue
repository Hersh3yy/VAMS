<template>
    <Card>
        <template #header>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-yellow-200">
                    Recent Entities
                </h3>
                <Link
                    :href="route('albums.index')"
                    class="hover:text-secondary/80 text-sm font-medium text-secondary"
                >
                    View all →
                </Link>
            </div>
        </template>

        <div v-if="entities.length === 0" class="py-8 text-center">
            <Icon name="collection" size="lg" class="mx-auto mb-4 text-gray-400" />
            <p class="text-gray-500 dark:text-yellow-400">No entities yet</p>
            <Link :href="route('albums.create')" class="btn-primary mt-4 inline-block">
                Create your first album
            </Link>
        </div>

        <div v-else class="space-y-1">
            <EntityCard
                v-for="entity in entities"
                :key="`${entity.type}-${entity.id}`"
                :entity="entity"
            />
        </div>
    </Card>
</template>

<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import Icon from '@/Components/Base/Icon.vue';
import EntityCard from '@/Components/atoms/EntityCard.vue';
import { Link } from '@inertiajs/vue3';

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
    entities: Entity[];
}

defineProps<Props>();
</script>
