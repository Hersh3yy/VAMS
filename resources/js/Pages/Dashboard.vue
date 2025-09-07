<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DashboardHeader from '@/Components/molecules/DashboardHeader.vue';
import StatsGrid from '@/Components/molecules/StatsGrid.vue';
import RecentEntitiesSection from '@/Components/molecules/RecentEntitiesSection.vue';
import RecentActivitiesSection from '@/Components/molecules/RecentActivitiesSection.vue';

interface Stats {
    totalAlbums: number;
    totalMosaics: number;
    totalImages: number;
    totalVideos: number;
}

interface Entity {
    id: string | number;
    type: 'album' | 'mosaic';
    title: string;
    description?: string;
    cover_image_path?: string;
    items_count: number;
    created_at: string;
    url: string;
}

interface Activity {
    id: number;
    type: 'create' | 'update' | 'delete';
    description: string;
    created_at: string;
}

interface Props {
    user: {
        name: string;
    };
    stats: Stats;
    recentAlbums: Array<{
        id: number;
        title: string;
        description?: string;
        cover_image_path?: string;
        images_count: number;
        created_at: string;
    }>;
    recentMosaics: Array<{
        id: string;
        title: string;
        description?: string;
        items_count: number;
        created_at: string;
    }>;
    recentEntities: Entity[];
    recentActivities: Activity[];
}

defineProps<Props>();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <DashboardHeader :user-name="user.name" />
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <!-- Stats Grid -->
                <StatsGrid :stats="stats" />

                <!-- Recent Content Grid -->
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Recent Entities -->
                    <RecentEntitiesSection :entities="recentEntities" />

                    <!-- Recent Activities -->
                    <RecentActivitiesSection :activities="recentActivities" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>