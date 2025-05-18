<template>
    <div class="tile-controls absolute top-2 right-2 flex gap-2 z-50">
        <button 
            @click="$emit('split')" 
            class="p-2 bg-white/90 hover:bg-white rounded-full shadow-lg"
            title="Split Tile">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M4 4v12h12V4H4zm10 10H6V6h8v8z"/>
            </svg>
        </button>
        <button 
            @click="$emit('image')" 
            class="p-2 bg-white/90 hover:bg-white rounded-full shadow-lg"
            title="Edit Image">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </button>
        <button 
            @click="$emit('delete')" 
            class="p-2 bg-white/90 hover:bg-white rounded-full shadow-lg"
            title="Delete Tile">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>
</template>

<script setup lang="ts">
defineEmits(['split', 'image', 'delete']);
</script>

<style scoped>
.tile-controls {
    opacity: 0;
    transition: opacity 0.2s;
}

.tile-controls:hover {
    opacity: 1;
}

button {
    transition: transform 0.2s;
}

button:hover {
    transform: scale(1.1);
}
</style> 