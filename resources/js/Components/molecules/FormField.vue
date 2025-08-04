<template>
  <div class="form-field">
    <label v-if="label" :for="inputId" class="form-field__label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <BaseInput
      :id="inputId"
      v-model="inputValue"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :error="error"
      :size="size"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    
    <p v-if="helpText" class="form-field__help">{{ helpText }}</p>
  </div>
</template>

<script setup lang="ts">
import { BaseInput } from '@/Components/atoms'

interface Props {
  modelValue: string
  label?: string
  type?: string
  placeholder?: string
  disabled?: boolean
  error?: string
  helpText?: string
  required?: boolean
  size?: 'sm' | 'md' | 'lg'
  id?: string
}

interface Emits {
  (e: 'update:modelValue', value: string): void
  (e: 'blur', event: FocusEvent): void
  (e: 'focus', event: FocusEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  required: false,
  size: 'md'
})

const emit = defineEmits<Emits>()

const inputId = computed(() => props.id || `input-${Math.random().toString(36).substr(2, 9)}`)

const inputValue = computed({
  get: () => props.modelValue,
  set: (value: string) => emit('update:modelValue', value)
})
</script>

<style scoped>
.form-field {
  @apply space-y-1;
}

.form-field__label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-field__help {
  @apply text-sm text-gray-500 dark:text-gray-400;
}

.required {
  @apply text-red-500 ml-1;
}
</style> 