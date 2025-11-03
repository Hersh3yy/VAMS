<template>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Uploading Images</h3>
        <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span>{{ completedCount }} complete</span>
            <span v-if="errorCount > 0" class="text-red-600">{{ errorCount }} failed</span>
            <span v-if="pendingCount > 0">{{ pendingCount }} remaining</span>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    completedCount: number;
    errorCount: number;
    pendingCount: number;
}>();
</script>

