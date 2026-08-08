<script setup lang="ts">
import { computed, ref, useTemplateRef } from 'vue';
import { onClickOutside, onKeyStroke } from '@vueuse/core';

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right';
        width?: '48';
        contentClasses?: string;
    }>(),
    {
        align: 'right',
        width: '48',
        contentClasses: 'py-1 bg-white dark:bg-gray-700'
    }
);

const open = ref(false);
const rootRef = useTemplateRef<HTMLElement>('rootRef');
const triggerRef = useTemplateRef<HTMLElement>('triggerRef');

function close() {
    if (!open.value) return;
    open.value = false;
    triggerRef.value?.querySelector<HTMLElement>('button, a, [tabindex]')?.focus();
}

function toggle() {
    open.value = !open.value;
}

onClickOutside(rootRef, close);

onKeyStroke('Escape', () => close());

const widthClass = computed(() => {
    return {
        48: 'w-48'
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    } else {
        return 'origin-top';
    }
});
</script>

<template>
    <div ref="rootRef" class="relative">
        <div ref="triggerRef" @click="toggle">
            <slot name="trigger" :open="open" :toggle="toggle" />
        </div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                role="menu"
                class="absolute z-50 mt-2 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
                @click="close"
            >
                <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
