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

        <Input
            :id="id"
            :model-value="modelValue"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :error="error"
            @update:model-value="$emit('update:modelValue', $event)"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        />

        <p v-if="helpText" class="text-sm text-gray-500 dark:text-gray-400">
            {{ helpText }}
        </p>
    </div>
</template>

<script setup lang="ts">
import Input from '@/Components/Base/Input.vue';

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

defineEmits<{
    'update:modelValue': [value: string];
    blur: [event: FocusEvent];
    focus: [event: FocusEvent];
}>();
</script>
