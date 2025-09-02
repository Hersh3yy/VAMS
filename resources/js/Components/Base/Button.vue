<template>
    <component
        :is="as"
        :type="as === 'button' ? type : undefined"
        :disabled="disabled || loading"
        :class="buttonClasses"
        :href="as === 'a' ? href : undefined"
        @click="$emit('click', $event)"
    >
        <span v-if="loading" class="-ml-1 mr-2 h-4 w-4 animate-spin">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                />
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
            </svg>
        </span>
        <slot />
    </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
    loading?: boolean;
    type?: 'button' | 'submit' | 'reset';
    iconOnly?: boolean;
    as?: 'button' | 'a' | 'label';
    href?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    disabled: false,
    loading: false,
    type: 'button',
    iconOnly: false,
    as: 'button'
});

defineEmits<{
    click: [event: MouseEvent];
}>();

const buttonClasses = computed(() => {
    const baseClasses = `inline-flex items-center rounded-md border font-semibold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 ${props.iconOnly ? '' : 'uppercase tracking-widest'}`;

    const sizeClasses = {
        sm: props.iconOnly ? 'p-1.5 text-xs' : 'px-3 py-1.5 text-xs',
        md: props.iconOnly ? 'p-2 text-xs' : 'px-4 py-2 text-xs',
        lg: props.iconOnly ? 'p-3 text-sm' : 'px-6 py-3 text-sm'
    };

    const variantClasses = {
        primary: 'btn-primary',
        secondary:
            'border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800',
        danger: 'border-transparent bg-red-600 text-white hover:bg-red-700 focus:bg-red-700 active:bg-red-800 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:bg-red-600 dark:active:bg-red-700',
        ghost: 'border-transparent bg-transparent text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
    };

    return `${baseClasses} ${sizeClasses[props.size]} ${variantClasses[props.variant]}`;
});
</script>
