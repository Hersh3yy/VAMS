<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DashboardStats from '@/Components/shared/DashboardStats.vue';

interface Props {
    auth: {
        user: {
            name: string;
        };
    };
    stats: {
        totalAlbums: number;
        totalMosaics: number;
        totalMedia: number;
    };
    recentActivity: Array<{
        id: number;
        type: 'create' | 'update' | 'delete';
        description: string;
        user: {
            name: string;
        };
        created_at: string;
    }>;
}

defineProps<Props>();
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout :auth="auth">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <DashboardStats
                    :stats="stats"
                    :recent-activity="recentActivity"
                />
            </div>
        </div>
    </AdminLayout>
</template>
