<template>
    <div v-if="displaySettings !== false" class="mb-4">
        <label :for="id" class="block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <Input
            :id="id"
            :model-value="localValue"
            :type="type"
            :placeholder="placeholder"
            :required="required"
            :error="error"
            @update:model-value="$emit('update:modelValue', $event)"
        />
        <p v-if="helpText" class="mt-1 text-xs text-gray-400">
            {{ helpText }}
        </p>
    </div>
</template>

<script setup lang="ts">
import Input from '@/Components/Base/Input.vue';
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
</script>
