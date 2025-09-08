<template>
    <Link :href="href" :class="buttonClass">
        {{ title }}
    </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title: string;
    href: string;
    variant?: 'primary' | 'secondary';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary'
});

const buttonClass = computed(() => {
    const baseClass = 'rounded p-4 text-center transition-colors duration-200';
    const variants = {
        primary: 'btn-primary',
        secondary: 'btn-secondary'
    };
    return `${baseClass} ${variants[props.variant]}`;
});
</script>
