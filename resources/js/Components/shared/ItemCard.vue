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
                    >
                    <div
                        v-else
                        class="cover-image bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center"
                    >
                        <slot name="placeholder" :item="item">
                            <svg 
                                class="w-16 h-16 text-white" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/>
                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" stroke-width="2"/>
                            </svg>
                        </slot>
                    </div>
                </slot>
                
                <!-- Action overlay -->
                <div 
                    v-if="actions.length > 0"
                    class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4"
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
                <h3 class="text-lg font-semibold dark:text-yellow-200">{{ item.title }}</h3>
                <p class="text-gray-600 mt-2 dark:text-yellow-400">
                    {{ item.description || 'No description' }}
                </p>
                
                <slot name="footer" :item="item">
                    <div class="text-sm text-gray-500 mt-2">
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
                <h3 class="text-lg font-semibold dark:text-yellow-200">{{ item.title }}</h3>
                <p class="text-gray-600 mt-2 dark:text-yellow-400">
                    {{ item.description || 'No description' }}
                </p>
                
                <slot name="footer" :item="item">
                    <div class="text-sm text-gray-500 mt-2">
                        {{ getItemCount(item) }}
                    </div>
                </slot>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts" generic="T extends { id: string | number; title?: string; description?: string; cover_image_path?: string }">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

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
    @apply bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 dark:bg-gray-800 dark:border-gray-700;
}

.image-container {
    @apply aspect-video bg-gray-100 relative overflow-hidden;
}

.cover-image {
    @apply w-full h-full object-cover;
}

.card-content {
    @apply p-4;
}

.group:hover .cover-image {
    @apply transform scale-105 transition-transform duration-200;
}
</style> 