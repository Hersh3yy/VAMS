<template>
    <div class="content-grid-wrapper">
        <!-- Empty state -->
        <div v-if="!items || items.length === 0" class="py-12 text-center">
            <img
                src="/images/placeholder.svg"
                alt="No items"
                class="mx-auto mb-4 h-12 w-12 text-gray-400"
            />
            <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-yellow-200">
                {{ emptyTitle }}
            </h3>
            <p class="mb-4 text-gray-600 dark:text-yellow-400">
                {{ emptyMessage }}
            </p>
            <Link :href="createRoute" class="btn-primary">
                {{ emptyButtonText }}
            </Link>
        </div>

        <!-- Items grid -->
        <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="group overflow-hidden rounded-lg border border-gray-200 bg-white shadow-md transition-shadow hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
            >
                <Link :href="getItemRoute(item)" class="block">
                    <div class="relative aspect-video overflow-hidden bg-gray-100">
                        <!-- Image slot -->
                        <slot name="image" :item="item">
                            <img
                                v-if="item.cover_image_path"
                                :src="item.cover_image_path"
                                :alt="item.title || 'Item image'"
                                class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-400 to-gray-600"
                            >
                                <img
                                    src="/images/placeholder.svg"
                                    alt="No image"
                                    class="h-16 w-16 opacity-50"
                                />
                            </div>
                        </slot>

                        <!-- Actions slot -->
                        <slot name="actions" :item="item" />
                    </div>

                    <!-- Content slot -->
                    <div class="p-4">
                        <slot name="content" :item="item">
                            <h3 class="text-lg font-semibold dark:text-yellow-200">
                                {{ item.title }}
                            </h3>
                            <p class="mt-2 text-gray-600 dark:text-yellow-400">
                                {{ item.description || '' }}
                            </p>
                        </slot>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>

<script
    setup
    lang="ts"
    generic="
        T extends {
            id: string | number;
            title?: string;
            description?: string;
            cover_image_path?: string;
        }
    "
>
import { Link } from '@inertiajs/vue3';

interface Props {
    items: T[];
    createRoute: string;
    emptyTitle: string;
    emptyMessage: string;
    emptyButtonText: string;
    getItemRoute: (item: T) => string;
}

defineProps<Props>();
</script>
