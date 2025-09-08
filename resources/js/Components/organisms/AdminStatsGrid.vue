<template>
    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Total Users" :value="stats.total_users" variant="primary" />
        <StatCard
            title="Pending Approvals"
            :value="stats.pending_approvals"
            :variant="stats.pending_approvals > 0 ? 'danger' : 'success'"
        />
        <StatCard title="Total Albums" :value="stats.total_albums" variant="secondary" />
        <StatCard title="Admin Users" :value="stats.total_admins" variant="purple" />
    </div>
</template>

<script setup lang="ts">
import StatCard from '@/Components/atoms/StatCard.vue';

interface Stats {
    total_users: number;
    pending_approvals: number;
    total_albums: number;
    total_admins: number;
}

defineProps<{
    stats: Stats;
}>();
</script>
