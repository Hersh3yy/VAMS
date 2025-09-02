<template>
    <div v-if="displaySettings !== false" class="mb-4">
        <label :for="id" class="block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <Input
            v-if="type !== 'textarea'"
            :id="id"
            :model-value="localValue"
            :type="type"
            :placeholder="placeholder"
            :required="required"
            :readonly="readonly"
            :error="error"
            @update:model-value="$emit('update:modelValue', $event)"
        />
        <Textarea
            v-else
            :id="id"
            :model-value="localValue"
            :placeholder="placeholder"
            :required="required"
            :rows="rows"
            :error="error"
            @update:model-value="$emit('update:modelValue', $event)"
        />
    </div>
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
</template>

<script setup lang="ts">
import Input from '@/Components/Base/Input.vue';
import Textarea from '@/Components/Base/Textarea.vue';
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
</script>
