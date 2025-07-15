<template>
    <div
        class="mosaic-tile-controls absolute right-2 top-2 flex gap-2 opacity-0 transition-opacity group-hover:opacity-100"
    >
        <button
            @click="$emit('split')"
            class="rounded-full bg-white p-2 shadow-lg hover:bg-gray-100"
            title="Split Tile"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                />
            </svg>
        </button>
        <button
            @click="$emit('image')"
            class="rounded-full bg-white p-2 shadow-lg hover:bg-gray-100"
            title="Add Image"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>
        <button
            @click="$emit('delete')"
            class="rounded-full bg-white p-2 shadow-lg hover:bg-gray-100"
            title="Delete Tile"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-red-500"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>
    </div>
</template>

<script setup lang="ts">
defineEmits<{
    split: [];
    image: [];
    delete: [];
}>();
</script>

<style scoped>
.mosaic-tile-controls {
    z-index: 10;
}

.mosaic-tile-controls button {
    transition: all 0.2s ease;
}

.mosaic-tile-controls button:hover {
    transform: scale(1.1);
}
</style>
