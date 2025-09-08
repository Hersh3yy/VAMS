<template>
    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <!-- Filter Options -->
            <div class="mb-4 flex justify-between">
                <div class="flex space-x-2">
                    <FilterButton
                        label="All Users"
                        :is-active="activeFilter === 'all'"
                        @click="$emit('filter-change', 'all')"
                    />
                    <FilterButton
                        label="Active"
                        :is-active="activeFilter === 'active'"
                        @click="$emit('filter-change', 'active')"
                    />
                    <FilterButton
                        label="Inactive"
                        :is-active="activeFilter === 'inactive'"
                        @click="$emit('filter-change', 'inactive')"
                    />
                </div>

                <!-- Search -->
                <div class="flex items-center space-x-2">
                    <input
                        :value="searchQuery"
                        type="text"
                        placeholder="Search users..."
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @input="$emit('search', ($event.target as HTMLInputElement).value)"
                    />
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                User
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                Email
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                Created
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300"
                                        >
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ user.name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                {{ user.email }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        user.email_verified_at
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800'
                                    ]"
                                >
                                    {{ user.email_verified_at ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <div class="flex space-x-2">
                                    <Link
                                        :href="route('admin.users.edit', user.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="$emit('delete-user', user.id)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination" class="mt-4 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing {{ pagination.from }} to {{ pagination.to }} of
                    {{ pagination.total }} results
                </div>
                <div class="flex space-x-2">
                    <Link
                        v-if="pagination.prev_page_url"
                        :href="pagination.prev_page_url"
                        class="rounded bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                    >
                        Previous
                    </Link>
                    <Link
                        v-if="pagination.next_page_url"
                        :href="pagination.next_page_url"
                        class="rounded bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                    >
                        Next
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import FilterButton from '@/Components/atoms/FilterButton.vue';
import { Link } from '@inertiajs/vue3';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    created_at: string;
}

interface Pagination {
    from: number;
    to: number;
    total: number;
    prev_page_url?: string;
    next_page_url?: string;
}

interface Props {
    users: User[];
    activeFilter: string;
    searchQuery: string;
    pagination?: Pagination;
}

defineProps<Props>();

defineEmits<{
    'filter-change': [filter: string];
    search: [query: string];
    'delete-user': [userId: number];
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>
