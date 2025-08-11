<template>
    <div v-if="displaySettings !== false" class="mb-4">
        <label :for="id" class="block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <input
            :id="id"
            :value="localValue"
            :type="type"
            :placeholder="placeholder"
            :required="required"
            :class="inputClasses"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        >
        <p v-if="helpText" class="mt-1 text-xs text-gray-400">
            {{ helpText }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string;
    label: string;
    id?: string;
    type?: 'text' | 'url' | 'datetime-local';
    placeholder?: string;
    required?: boolean;
    error?: string;
    helpText?: string;
    displaySettings?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    displaySettings: true
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const localValue = computed({
    get: () => props.modelValue,
    set: (value: string) => emit('update:modelValue', value)
});

const inputClasses = computed(() => {
    const baseClasses =
        'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400 dark:focus:ring-indigo-400';
    const errorClasses = props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600 dark:focus:border-red-400 dark:focus:ring-red-400'
        : '';

    return `${baseClasses} ${errorClasses}`;
});
</script>
