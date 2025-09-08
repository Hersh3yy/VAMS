<template>
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
    />
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    id?: string;
    type?: string;
    modelValue: string;
    required?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    placeholder?: string;
    disabled?: boolean;
    hasError?: boolean;
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
}>();

const inputClass = computed(() => {
    const baseClass =
        'block w-full rounded-md border shadow-sm transition-colors duration-200 focus:ring-2';
    const sizeClasses = {
        sm: 'px-2 py-1 text-sm',
        md: 'px-3 py-2',
        lg: 'px-4 py-3 text-lg'
    };

    const stateClasses = props.hasError
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600'
        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:focus:border-indigo-600 dark:focus:ring-indigo-600';

    const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : '';

    return `${baseClass} ${sizeClasses[props.size]} ${stateClasses} ${disabledClass} dark:bg-gray-900 dark:text-gray-300`;
});
</script>
