<template>
    <Head :title="title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <h2 class="page-title">{{ title }}</h2>
                <Link :href="createRoute" class="btn-primary">
                    {{ createButtonText }}
                </Link>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <!-- Empty state -->
                <div v-if="items.length === 0" class="empty-state">
                    <slot name="empty-icon">
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            class="h-12 w-12 mx-auto text-gray-400 mb-4" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            <path 
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                stroke-width="2" 
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" 
                            />
                        </svg>
                    </slot>
                    <h3 class="text-lg font-medium text-gray-900 mb-2 dark:text-yellow-200">
                        {{ emptyStateTitle }}
                    </h3>
                    <p class="text-gray-600 mb-4 dark:text-yellow-400">
                        {{ emptyStateMessage }}
                    </p>
                    <Link :href="createRoute" class="btn-primary">
                        {{ emptyStateButtonText }}
                    </Link>
                </div>

                <!-- Items grid -->
                <div v-else class="items-grid">
                    <slot name="items" :items="items">
                        <!-- Default item rendering -->
                        <ItemCard
                            v-for="item in items"
                            :key="item.id"
                            :item="item"
                            :href="getItemRoute(item)"
                            :actions="getItemActions(item)"
                        >
                            <template #image="{ item }">
                                <slot name="item-image" :item="item">
                                    <!-- Default image slot -->
                                </slot>
                            </template>
                            <template #placeholder="{ item }">
                                <slot name="item-placeholder" :item="item">
                                    <!-- Default placeholder slot -->
                                </slot>
                            </template>
                            <template #footer="{ item }">
                                <slot name="item-footer" :item="item">
                                    <!-- Default footer slot -->
                                </slot>
                            </template>
                        </ItemCard>
                    </slot>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts" generic="T extends { id: string | number; title?: string; description?: string }">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ItemCard from '@/Components/shared/ItemCard.vue';

interface Props {
    title: string;
    items: T[];
    createRoute: string;
    createButtonText: string;
    emptyStateTitle: string;
    emptyStateMessage: string;
    emptyStateButtonText: string;
    getItemRoute: (item: T) => string;
    getItemActions?: (item: T) => Array<{
        label: string;
        handler: (item: T) => void;
        icon: string;
        class: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    getItemActions: () => () => [],
});
</script>

<style scoped>
.empty-state {
    @apply text-center py-12;
}

.items-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6;
}
</style> 