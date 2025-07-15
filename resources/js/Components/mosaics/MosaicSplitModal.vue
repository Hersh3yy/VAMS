<template>
    <Modal v-model="modelValue">
        <template #title>Split Direction</template>

        <div class="flex gap-4">
            <button
                @click="selectSplit('horizontal')"
                class="flex-1 rounded-lg border-2 p-4"
                :class="
                    direction === 'horizontal'
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200'
                "
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mx-auto h-8 w-8"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
                <span class="mt-2 block text-center text-sm">Horizontal</span>
            </button>

            <button
                @click="selectSplit('vertical')"
                class="flex-1 rounded-lg border-2 p-4"
                :class="
                    direction === 'vertical'
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200'
                "
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mx-auto h-8 w-8"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 4v16M12 4v16M18 4v16"
                    />
                </svg>
                <span class="mt-2 block text-center text-sm">Vertical</span>
            </button>
        </div>

        <template #footer>
            <button
                @click="confirmSplit"
                class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
            >
                Split Tile
            </button>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Modal from '../general/Modal.vue';

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    split: [direction: 'horizontal' | 'vertical'];
}>();

const direction = ref<'horizontal' | 'vertical'>('horizontal');

const selectSplit = (newDirection: 'horizontal' | 'vertical') => {
    direction.value = newDirection;
};

const confirmSplit = () => {
    emit('split', direction.value);
    emit('update:modelValue', false);
};
</script>
