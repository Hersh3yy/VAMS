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
                            <img 
                                src="/images/placeholder.svg" 
                                alt="No image" 
                                class="h-16 w-16 opacity-50"
                            />
                        </slot>
                    </div>
                </slot>

                <!-- Action overlay -->
                <div
                    v-if="actions.length > 0"
                    class="absolute inset-0 flex items-center justify-center gap-4 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <Button
                        v-for="action in actions"
                        :key="action.label"
                        @click.prevent="action.handler(item)"
                        :variant="getButtonVariant(action.class)"
                        size="sm"
                        icon-only
                        :title="action.label"
                    >
                        <component :is="action.icon" class="h-5 w-5" />
                    </Button>
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
import Button from '@/Components/Base/Button.vue';

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
    linkComponent: Link
});

const imageError = ref(false);

const handleImageError = () => {
    imageError.value = true;
};

const getButtonVariant = (actionClass: string): 'primary' | 'secondary' | 'danger' | 'ghost' => {
    if (actionClass.includes('btn-danger') || actionClass.includes('danger')) {
        return 'danger';
    }
    if (actionClass.includes('bg-secondary')) {
        return 'secondary';
    }
    return 'primary';
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
