<template>
    <Card>
        <template #header>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-yellow-200">
                    Recent Activities
                </h3>
            </div>
        </template>

        <div v-if="activities.length === 0" class="py-8 text-center">
            <Icon name="clock" size="lg" class="mx-auto mb-4 text-gray-400" />
            <p class="text-gray-500 dark:text-yellow-400">No recent activities</p>
        </div>

        <div v-else class="space-y-0 divide-y divide-gray-200 dark:divide-gray-700">
            <ActivityItem v-for="activity in activities" :key="activity.id" :activity="activity" />
        </div>
    </Card>
</template>

<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import Icon from '@/Components/Base/Icon.vue';
import ActivityItem from '@/Components/atoms/ActivityItem.vue';

interface Activity {
    id: number;
    type: 'create' | 'update' | 'delete';
    description: string;
    created_at: string;
}

interface Props {
    activities: Activity[];
}

defineProps<Props>();
</script>
