<template>
    <div v-if="displaySettings !== false" class="mb-4">
        <label :for="id" class="block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <input
            v-if="type !== 'textarea'"
            :id="id"
            :value="localValue"
            :type="type"
            :placeholder="placeholder"
            :required="required"
            :readonly="readonly"
            :class="inputClasses"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        >
        <textarea
            v-else
            :id="id"
            :value="localValue"
            :placeholder="placeholder"
            :required="required"
            :rows="rows"
            :class="inputClasses"
            @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        />
    </div>
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string;
    label: string;
    id?: string;
    type?: 'text' | 'url' | 'datetime-local' | 'textarea';
    placeholder?: string;
    required?: boolean;
    error?: string;
    readonly?: boolean;
    rows?: number;
    displaySettings?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    readonly: false,
    rows: 3,
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
    const disabledClasses = props.readonly ? 'bg-gray-100 cursor-not-allowed dark:bg-gray-800' : '';

    return `${baseClasses} ${errorClasses} ${disabledClasses}`;
});
</script>
