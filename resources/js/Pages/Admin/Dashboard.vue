<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
                <div>
                    <Link :href="route('admin.users.index')" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded">
                        Manage Users
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Stats Cards -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Users</h3>
                        <p class="text-3xl font-bold text-primary">{{ stats.total_users }}</p>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Pending Approvals</h3>
                        <p class="text-3xl font-bold" :class="stats.pending_approvals > 0 ? 'text-red-600' : 'text-green-600'">
                            {{ stats.pending_approvals }}
                        </p>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Albums</h3>
                        <p class="text-3xl font-bold text-secondary">{{ stats.total_albums }}</p>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Admin Users</h3>
                        <p class="text-3xl font-bold text-purple-600">{{ stats.total_admins }}</p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Quick Actions</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Link :href="route('admin.users.create')" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded text-center">
                            Create New User
                        </Link>
                        
                        <Link :href="route('admin.users.index', { filter: 'unapproved' })" class="bg-orange-500 hover:bg-orange-600 text-white p-4 rounded text-center">
                            View Pending Approvals
                        </Link>
                        
                        <Link :href="route('dashboard')" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded text-center">
                            Return to User Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    stats: Object,
});
</script> 