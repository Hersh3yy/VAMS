<template>
    <div class="mb-4">
        <div class="mb-2 flex justify-between text-sm text-gray-600">
            <span>Overall Progress</span>
            <span>{{ overallProgress }}%</span>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200">
            <div
                class="h-2 rounded-full transition-all duration-300"
                :class="{
                    'bg-blue-600': pendingCount > 0,
                    'bg-green-600': pendingCount === 0 && errorCount === 0,
                    'bg-yellow-500': pendingCount === 0 && errorCount > 0
                }"
                :style="{ width: `${overallProgress}%` }"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    overallProgress: number;
    pendingCount: number;
    errorCount: number;
}>();
</script>

