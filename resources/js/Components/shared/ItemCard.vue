<template>
    <div class="card group" :class="cardClasses">
        <component :is="linkComponent" :href="href" v-if="href" class="block">
            <div class="image-container" :class="imageContainerClasses">
                <slot name="image" :item="item">
                    <!-- Default image/preview -->
                    <img
                        v-if="item.cover_image_path && !imageError"
                        :src="item.cover_image_path"
                        :alt="item.title || 'Item image'"
                        class="cover-image"
                        @error="handleImageError"
                    />
                    <div
                        v-else
                        class="cover-image flex items-center justify-center bg-gradient-to-br from-gray-400 to-gray-600"
                    >
                        <slot name="placeholder" :item="item">
                            <svg
                                class="h-16 w-16 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <rect
                                    x="3"
                                    y="3"
                                    width="18"
                                    height="18"
                                    rx="2"
                                    ry="2"
                                    stroke-width="2"
                                />
                                <circle
                                    cx="8.5"
                                    cy="8.5"
                                    r="1.5"
                                    stroke-width="2"
                                />
                                <path
                                    d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"
                                    stroke-width="2"
                                />
                            </svg>
                        </slot>
                    </div>
                </slot>

                <!-- Action overlay -->
                <div
                    v-if="actions.length > 0"
                    class="absolute inset-0 flex items-center justify-center gap-4 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <button
                        v-for="action in actions"
                        :key="action.label"
                        @click.prevent="action.handler(item)"
                        :class="action.class"
                        :title="action.label"
                    >
                        <component :is="action.icon" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="card-content">
                <h3 class="text-lg font-semibold dark:text-yellow-200">
                    {{ item.title }}
                </h3>
                <p class="mt-2 text-gray-600 dark:text-yellow-400">
                    {{ item.description || 'No description' }}
                </p>

                <slot name="footer" :item="item">
                    <div class="mt-2 text-sm text-gray-500">
                        {{ getItemCount(item) }}
                    </div>
                </slot>
            </div>
        </component>

        <!-- Non-linked version -->
        <div v-else>
            <div class="image-container" :class="imageContainerClasses">
                <slot name="image" :item="item">
                    <!-- Same image content as above -->
                </slot>
            </div>

            <div class="card-content">
                <h3 class="text-lg font-semibold dark:text-yellow-200">
                    {{ item.title }}
                </h3>
                <p class="mt-2 text-gray-600 dark:text-yellow-400">
                    {{ item.description || 'No description' }}
                </p>

                <slot name="footer" :item="item">
                    <div class="mt-2 text-sm text-gray-500">
                        {{ getItemCount(item) }}
                    </div>
                </slot>
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
import { ref } from 'vue';

interface CardAction {
    label: string;
    handler: (item: T) => void;
    icon: string;
    class: string;
}

interface Props {
    item: T;
    href?: string;
    actions?: CardAction[];
    cardClasses?: string;
    imageContainerClasses?: string;
    linkComponent?: any;
}

const props = withDefaults(defineProps<Props>(), {
    actions: () => [],
    cardClasses: '',
    imageContainerClasses: '',
    linkComponent: Link,
});

const imageError = ref(false);

const handleImageError = () => {
    imageError.value = true;
};

const getItemCount = (item: T) => {
    // Try to get count from different possible properties
    const images = (item as any)?.images?.length;
    const items = (item as any)?.items?.length;
    const count = images || items || 0;

    if (images !== undefined) {
        return `${count} ${count === 1 ? 'item' : 'items'}`;
    } else if (items !== undefined) {
        return `${count} ${count === 1 ? 'tile' : 'tiles'}`;
    } else {
        return '';
    }
};
</script>

<style scoped>
.card {
    @apply overflow-hidden rounded-lg border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800;
}

.image-container {
    @apply relative aspect-video overflow-hidden bg-gray-100;
}

.cover-image {
    @apply h-full w-full object-cover;
}

.card-content {
    @apply p-4;
}

.group:hover .cover-image {
    @apply scale-105 transform transition-transform duration-200;
}
</style>
