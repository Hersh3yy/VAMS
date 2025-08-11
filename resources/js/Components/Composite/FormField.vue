<template>
    <div class="space-y-1">
        <label
            v-if="label"
            :for="id"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <input
            :id="id"
            :value="modelValue"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :class="inputClasses"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        >

        <p v-if="helpText" class="text-sm text-gray-500 dark:text-gray-400">
            {{ helpText }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string;
    label?: string;
    id?: string;
    type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url';
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string;
    helpText?: string;
}

const _props = withDefaults(defineProps<Props>(), {
    type: 'text',
    disabled: false,
    required: false
});

const inputClasses = computed(() => {
    const baseClasses =
        'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400 dark:focus:ring-indigo-400';
    const errorClasses = _props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600 dark:focus:border-red-400 dark:focus:ring-red-400'
        : '';
    const disabledClasses = _props.disabled
        ? 'bg-gray-100 cursor-not-allowed dark:bg-gray-800'
        : '';

    return `${baseClasses} ${errorClasses} ${disabledClasses}`;
});

defineEmits<{
    'update:modelValue': [value: string];
    blur: [event: FocusEvent];
    focus: [event: FocusEvent];
}>();
</script>
