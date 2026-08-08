<template>
    <div :class="containerClass">
        <BaseLabel v-if="label" :text="label" :for-id="id" :required="required" />

        <div :class="inputWrapperClass">
            <BaseInput
                v-if="type !== 'textarea'"
                :id="id"
                :type="type"
                :model-value="modelValue"
                :required="required"
                :autofocus="autofocus"
                :autocomplete="autocomplete"
                :placeholder="placeholder"
                :disabled="disabled"
                :has-error="!!error"
                :size="size"
                @update:model-value="$emit('update:modelValue', $event)"
            />
            
            <textarea
                v-else
                :id="id"
                :value="modelValue"
                :required="required"
                :autofocus="autofocus"
                :placeholder="placeholder"
                :disabled="disabled"
                :rows="rows"
                :class="textareaClasses"
                @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
            />

            <slot name="append" />
        </div>

        <BaseErrorMessage :error="error" />

        <p v-if="hint" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ hint }}
        </p>
    </div>
</template>

<script setup lang="ts">
import BaseErrorMessage from '@/Components/Base/ErrorMessage.vue';
import BaseInput from '@/Components/Base/Input.vue';
import BaseLabel from '@/Components/Base/Label.vue';
import { computed } from 'vue';

interface Props {
    id?: string;
    label?: string;
    type?: string;
    modelValue: string;
    required?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    hint?: string;
    size?: 'sm' | 'md' | 'lg';
    spacing?: 'sm' | 'md' | 'lg';
    rows?: number;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    autofocus: false,
    disabled: false,
    size: 'md',
    spacing: 'md',
    rows: 4
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const containerClass = computed(() => {
    const spacingClasses = {
        sm: 'mb-3',
        md: 'mb-4',
        lg: 'mb-6'
    };
    return spacingClasses[props.spacing];
});

const inputWrapperClass = computed(() => {
    return props.label ? 'mt-1' : '';
});

const textareaClasses = computed(() => {
    const baseClasses = 'block w-full rounded-md border shadow-sm transition-colors duration-200 focus:ring-2 focus:outline-none';
    const errorClasses = props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600'
        : 'border-gray-300 focus:border-secondary focus:ring-secondary dark:border-gray-600 dark:focus:border-secondary dark:focus:ring-indigo-400';
    const disabledClasses = props.disabled ? 'bg-gray-100 cursor-not-allowed dark:bg-gray-800' : 'dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
    
    return `${baseClasses} ${errorClasses} ${disabledClasses} px-3 py-2`;
});
</script>
