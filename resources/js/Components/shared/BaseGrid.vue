<template>
    <div 
        ref="gridContainer"
        class="grid gap-4"
        :class="gridClasses"
        :style="gridStyles"
    >
        <div
            v-for="(item, index) in localItems"
            :key="item.id"
            :data-id="item.id"
            :data-index="index"
            class="relative group"
            :class="itemClasses"
        >
            <slot 
                name="item"
                :item="item as T"
                :index="index"
                :on-click="() => handleItemClick(item as T)"
                :on-delete="() => handleItemDelete(item as T)"
            >
                <!-- Default slot content if no slot provided -->
                <div class="bg-gray-200 rounded-lg p-4">
                    <div class="text-sm text-gray-600">{{ (item as any)?.title || `Item ${item.id}` }}</div>
                </div>
            </slot>
        </div>
    </div>
</template>

<script setup lang="ts" generic="T extends { id: string | number }">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

interface GridSettings {
    columns?: number;
    gap?: number;
    padding?: number;
    responsive?: boolean;
}

interface Props {
    items: T[];
    settings?: GridSettings;
    draggable?: boolean;
    dragType?: 'sortable' | 'html5';
    itemClasses?: string;
}

const props = withDefaults(defineProps<Props>(), {
    settings: () => ({ columns: 3, gap: 16, padding: 0, responsive: true }),
    draggable: false,
    dragType: 'sortable',
    itemClasses: '',
});

const emit = defineEmits<{
    (e: 'item-click', item: T): void;
    (e: 'item-delete', item: T): void;
    (e: 'reorder', from: number | string, to: number | string): void;
}>();

const gridContainer = ref<HTMLElement | null>(null);
const localItems = ref<T[]>([...props.items]);
let sortableInstance: any = null;

// Watch for prop changes
watch(() => props.items, (newItems) => {
    localItems.value = [...newItems];
}, { deep: true });

const gridClasses = computed(() => {
    const classes = [];
    
    if (props.settings.responsive) {
        // Responsive grid classes
        classes.push('grid-cols-1');
        if (props.settings.columns! >= 2) classes.push('md:grid-cols-2');
        if (props.settings.columns! >= 3) classes.push('lg:grid-cols-3');
        if (props.settings.columns! >= 4) classes.push('xl:grid-cols-4');
    } else {
        // Fixed grid classes
        classes.push(`grid-cols-${props.settings.columns}`);
    }
    
    return classes;
});

const gridStyles = computed(() => {
    const styles: Record<string, string> = {};
    
    if (props.settings.gap) {
        styles.gap = `${props.settings.gap}px`;
    }
    
    if (props.settings.padding) {
        styles.padding = `${props.settings.padding}px`;
    }
    
    return styles;
});

const handleItemClick = (item: T) => {
    emit('item-click', item);
};

const handleItemDelete = (item: T) => {
    emit('item-delete', item);
};

const handleReorder = (from: number | string, to: number | string) => {
    emit('reorder', from, to);
};

// SortableJS implementation
const initializeSortable = async () => {
    if (!props.draggable || props.dragType !== 'sortable' || !gridContainer.value) return;
    
    try {
        const { default: Sortable } = await import('sortablejs');
        
        sortableInstance = Sortable.create(gridContainer.value, {
            animation: 150,
            ghostClass: 'opacity-50 bg-blue-100 border-2 border-blue-300 border-dashed',
            chosenClass: 'ring-2 ring-blue-500 transform scale-105',
            dragClass: 'transform rotate-3 shadow-lg',
            onEnd: (evt: any) => {
                if (evt.oldIndex !== evt.newIndex) {
                    handleReorder(evt.oldIndex, evt.newIndex);
                }
            }
        });
    } catch (error) {
        console.warn('SortableJS not available:', error);
    }
};

onMounted(() => {
    initializeSortable();
});

onUnmounted(() => {
    if (sortableInstance) {
        sortableInstance.destroy();
    }
});

// Watch for draggable changes
watch([() => props.draggable, () => props.dragType], () => {
    if (sortableInstance) {
        sortableInstance.destroy();
        sortableInstance = null;
    }
    initializeSortable();
});
</script>

<style scoped>
.group {
    @apply transition-all duration-200 ease-in-out;
}

.group:hover {
    @apply transform -translate-y-1 shadow-lg;
}
</style> 