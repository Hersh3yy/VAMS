<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Admin Dashboard</h2>
                <div>
                    <Link
                        :href="route('admin.users.index')"
                        class="hover:bg-primary/90 rounded bg-primary px-4 py-2 text-white"
                    >
                        Manage Users
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <AdminStatsGrid :stats="stats" />
                <AdminQuickActions />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AdminStatsGrid from '@/Components/organisms/AdminStatsGrid.vue';
import AdminQuickActions from '@/Components/organisms/AdminQuickActions.vue';
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
