<template>
    <div class="relative">
        <input
            :id="id"
            :type="type"
            :class="inputClass"
            :value="modelValue"
            :required="required"
            :autofocus="autofocus"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :disabled="disabled"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        />
        <div v-if="error" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
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
    id?: string;
    type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'datetime-local' | string;
    modelValue: string;
    required?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    placeholder?: string;
    disabled?: boolean;
    hasError?: boolean;
    error?: string;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    autofocus: false,
    disabled: false,
    hasError: false,
    size: 'md'
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    blur: [event: FocusEvent];
    focus: [event: FocusEvent];
}>();

const inputClass = computed(() => {
    const baseClass =
        'block w-full rounded-md border shadow-sm transition-colors duration-200 focus:ring-2';
    const sizeClasses = {
        sm: 'px-2 py-1 text-sm',
        md: 'px-3 py-2',
        lg: 'px-4 py-3 text-lg'
    };

    // Use error prop if provided, otherwise fall back to hasError
    const showError = !!props.error || props.hasError;
    const stateClasses = showError
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600 dark:bg-gray-700 dark:text-white dark:focus:border-red-400 dark:focus:ring-red-400'
        : 'border-gray-300 focus:border-secondary focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-secondary dark:focus:ring-indigo-400';

    const disabledClass = props.disabled ? 'bg-gray-100 cursor-not-allowed dark:bg-gray-800' : '';

    return `${baseClass} ${sizeClasses[props.size]} ${stateClasses} ${disabledClass}`;
});
</script>
