<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Admin Dashboard
                </h2>
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
                <div
                    class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4"
                >
                    <!-- Stats Cards -->
                    <div
                        class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <h3 class="mb-2 text-lg font-semibold text-gray-700">
                            Total Users
                        </h3>
                        <p class="text-3xl font-bold text-primary">
                            {{ stats.total_users }}
                        </p>
                    </div>

                    <div
                        class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <h3 class="mb-2 text-lg font-semibold text-gray-700">
                            Pending Approvals
                        </h3>
                        <p
                            class="text-3xl font-bold"
                            :class="
                                stats.pending_approvals > 0
                                    ? 'text-red-600'
                                    : 'text-green-600'
                            "
                        >
                            {{ stats.pending_approvals }}
                        </p>
                    </div>

                    <div
                        class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <h3 class="mb-2 text-lg font-semibold text-gray-700">
                            Total Albums
                        </h3>
                        <p class="text-3xl font-bold text-secondary">
                            {{ stats.total_albums }}
                        </p>
                    </div>

                    <div
                        class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <h3 class="mb-2 text-lg font-semibold text-gray-700">
                            Admin Users
                        </h3>
                        <p class="text-3xl font-bold text-purple-600">
                            {{ stats.total_admins }}
                        </p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                >
                    <h3 class="mb-4 text-lg font-semibold text-gray-700">
                        Quick Actions
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <Link
                            :href="route('admin.users.create')"
                            class="btn-primary rounded p-4 text-center"
                        >
                            Create New User
                        </Link>

                        <Link
                            :href="
                                route('admin.users.index', {
                                    filter: 'unapproved',
                                })
                            "
                            class="btn-secondary rounded p-4 text-center"
                        >
                            View Pending Approvals
                        </Link>

                        <Link
                            :href="route('dashboard')"
                            class="btn-primary rounded p-4 text-center"
                        >
                            Return to User Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>
