<template>
    <FormField
        :id="id"
        :label="label"
        :model-value="modelValue"
        :required="required"
        :autofocus="autofocus"
        :autocomplete="autocomplete"
        :placeholder="placeholder"
        :disabled="disabled"
        :error="error"
        :hint="hint"
        :size="size"
        :spacing="spacing"
        :type="showPassword ? 'text' : 'password'"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <template #append>
            <button
                type="button"
                @click="togglePasswordVisibility"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
            >
                <svg
                    v-if="showPassword"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
                    />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                </svg>
            </button>
        </template>
    </FormField>
</template>

<script setup lang="ts">
import FormField from '@/Components/molecules/FormField.vue';
import { ref } from 'vue';

interface Props {
    id?: string;
    label?: string;
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

defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>
