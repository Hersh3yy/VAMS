<template>
    <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
        <h3 class="mb-4 text-lg font-semibold text-gray-700">Quick Actions</h3>
        
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <QuickActionCard 
                title="Create New User" 
                :href="route('admin.users.create')"
                variant="primary"
            />
            <QuickActionCard 
                title="View Pending Approvals" 
                :href="route('admin.users.index', { filter: 'unapproved' })"
                variant="secondary"
            />
            <QuickActionCard 
                title="Return to User Dashboard" 
                :href="route('dashboard')"
                variant="primary"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import QuickActionCard from '@/Components/molecules/QuickActionCard.vue';
</script>
