<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold leading-tight text-gray-800">Admin Dashboard</h1>
                <div class="flex space-x-3">
                    <Link
                        :href="route('admin.users.index')"
                        class="hover:bg-primary/90 rounded bg-primary px-4 py-2 text-white"
                    >
                        Manage Users
                    </Link>
                    <Link
                        :href="route('admin.entry-types.index')"
                        class="hover:bg-green-600 rounded bg-green-500 px-4 py-2 text-white"
                    >
                        Manage Entry Types
                    </Link>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <AdminStatsGrid :stats="stats" />
                <AdminQuickActions />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AdminQuickActions from '@/Components/admin/AdminQuickActions.vue';
import AdminStatsGrid from '@/Components/admin/AdminStatsGrid.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

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
