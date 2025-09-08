<template>
    <div :class="containerClass">
        <BaseLabel 
            v-if="label" 
            :text="label" 
            :for-id="id" 
            :required="required" 
        />
        
        <div :class="inputWrapperClass">
            <BaseInput
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
            
            <slot name="append" />
        </div>
        
        <BaseErrorMessage :error="error" />
        
        <p v-if="hint" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ hint }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import BaseLabel from '@/Components/atoms/BaseLabel.vue';
import BaseInput from '@/Components/atoms/BaseInput.vue';
import BaseErrorMessage from '@/Components/atoms/BaseErrorMessage.vue';

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
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    autofocus: false,
    disabled: false,
    size: 'md',
    spacing: 'md'
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
</script>