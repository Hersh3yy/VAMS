<template>
    <div class="relative">
        <textarea
            :id="id"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            :class="textareaClasses"
            @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        />
        <div v-if="error" class="absolute inset-y-0 right-0 flex items-center pr-3">
            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
    </div>
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string;
    id?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string;
    rows?: number;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    required: false,
    rows: 3
});

defineEmits<{
    'update:modelValue': [value: string];
    blur: [event: FocusEvent];
    focus: [event: FocusEvent];
}>();

const textareaClasses = computed(() => {
    const baseClasses =
        'block w-full rounded-md border-gray-300 shadow-sm focus:border-secondary focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-secondary dark:focus:ring-indigo-400';
    const errorClasses = props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600 dark:focus:border-red-400 dark:focus:ring-red-400'
        : '';
    const disabledClasses = props.disabled ? 'bg-gray-100 cursor-not-allowed dark:bg-gray-800' : '';

    return `${baseClasses} ${errorClasses} ${disabledClasses}`;
});
</script>
