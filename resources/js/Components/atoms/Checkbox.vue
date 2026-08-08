<template>
    <div class="flex items-center">
        <input
            :id="id"
            type="checkbox"
            :checked="modelValue"
            @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
            :class="inputClass"
        />
        <label
            v-if="label"
            :for="id"
            class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {{ label }}
        </label>
        <p v-if="hint" class="ml-2 text-xs text-gray-500 dark:text-gray-400">
            {{ hint }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    id?: string;
    modelValue: boolean;
    label?: string;
    hint?: string;
}

const props = withDefaults(defineProps<Props>(), {
    id: undefined,
    label: '',
    hint: ''
});

defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const inputClass = computed(() => {
    return 'rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-600';
});
</script>

