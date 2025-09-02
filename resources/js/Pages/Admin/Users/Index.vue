<template>
    <Head title="Manage Users" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Manage Users</h2>
                <div class="flex space-x-3">
                    <Link
                        :href="route('test-api')"
                        class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                    >
                        Test API
                    </Link>
                    <Link
                        :href="route('admin.users.create')"
                        class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600"
                    >
                        Add New User
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                <div
                    v-if="$page.props.flash?.success"
                    class="relative mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700"
                    role="alert"
                >
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>
                <div
                    v-if="$page.props.flash?.error"
                    class="relative mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700"
                    role="alert"
                >
                    <span class="block sm:inline">{{ $page.props.flash.error }}</span>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Filter Options -->
                        <div class="mb-4 flex justify-between">
                            <div class="flex space-x-2">
                                <button
                                    @click="activeFilter = 'all'"
                                    :class="[
                                        'rounded px-3 py-1',
                                        activeFilter === 'all'
                                            ? 'bg-primary text-white'
                                            : 'bg-gray-200'
                                    ]"
                                >
                                    All Users
                                </button>
                                <button
                                    @click="activeFilter = 'admins'"
                                    :class="[
                                        'rounded px-3 py-1',
                                        activeFilter === 'admins'
                                            ? 'bg-primary text-white'
                                            : 'bg-gray-200'
                                    ]"
                                >
                                    Admins
                                </button>
                                <button
                                    @click="activeFilter = 'pending'"
                                    :class="[
                                        'rounded px-3 py-1',
                                        activeFilter === 'pending'
                                            ? 'bg-primary text-white'
                                            : 'bg-gray-200'
                                    ]"
                                >
                                    Pending Approval
                                </button>
                            </div>
                            <div>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search users..."
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Name
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Email
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Status
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Albums
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            API Key
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Created
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="user in filteredUsers"
                                        :key="user.id"
                                        :class="{
                                            'bg-yellow-50': !user.is_approved
                                        }"
                                    >
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ user.name }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-500">
                                                {{ user.email }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                v-if="user.is_admin"
                                                class="inline-flex rounded-full bg-purple-100 px-2 text-xs font-semibold leading-5 text-purple-800"
                                            >
                                                Admin
                                            </span>
                                            <span
                                                v-if="user.is_approved"
                                                class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800"
                                            >
                                                Approved
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800"
                                            >
                                                Pending
                                            </span>
                                        </td>
                                        <td
                                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                        >
                                            {{ user.albums_count }}
                                        </td>
                                        <td
                                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <code class="rounded bg-gray-100 px-2 py-1 text-xs">
                                                    {{
                                                        user.api_key
                                                            ? user.api_key.substring(0, 8) + '...'
                                                            : 'None'
                                                    }}
                                                </code>
                                                <button
                                                    v-if="user.api_key"
                                                    @click="copyApiKey(user.api_key)"
                                                    class="text-xs text-blue-600 hover:text-blue-500"
                                                    title="Copy API Key"
                                                >
                                                    Copy
                                                </button>
                                            </div>
                                        </td>
                                        <td
                                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                        >
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td
                                            class="space-x-2 whitespace-nowrap px-6 py-4 text-right text-sm"
                                        >
                                            <Link
                                                :href="route('admin.users.edit', user.id)"
                                                class="mr-2 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Edit
                                            </Link>

                                            <button
                                                v-if="!user.is_approved"
                                                @click="approveUser(user.id)"
                                                class="mr-2 text-green-600 hover:text-green-900"
                                            >
                                                Approve
                                            </button>

                                            <button
                                                @click="impersonateUser(user.id)"
                                                class="mr-2 text-blue-600 hover:text-blue-900"
                                            >
                                                Impersonate
                                            </button>

                                            <button
                                                @click="confirmDelete(user)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredUsers.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No users found matching your criteria.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="deleteModal" @close="deleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete User</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete {{ userToDelete?.name }}? This action cannot be
                    undone.
                </p>
                <div class="mt-6 flex justify-end">
                    <Button variant="secondary" @click="deleteModal = false" class="mr-3">
                        Cancel
                    </Button>
                    <button @click="deleteUser" class="btn-danger">Delete User</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import Button from '@/Components/Base/Button.vue';
import Modal from '@/Components/Base/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: Array
});

const search = ref('');
const activeFilter = ref('all');
const deleteModal = ref(false);
const userToDelete = ref(null);

// Computed property for filtered users
const filteredUsers = computed(() => {
    let result = props.users;

    // Apply search filter
    if (search.value) {
        const searchLower = search.value.toLowerCase();
        result = result.filter(
            user =>
                user.name.toLowerCase().includes(searchLower) ||
                user.email.toLowerCase().includes(searchLower)
        );
    }

    // Apply category filter
    if (activeFilter.value === 'admins') {
        result = result.filter(user => user.is_admin);
    } else if (activeFilter.value === 'pending') {
        result = result.filter(user => !user.is_approved);
    }

    return result;
});

// Format date for display
const formatDate = dateString => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(date);
};

// Form for DELETE request
const deleteForm = useForm({});

// Form for POST requests (approve, impersonate)
const actionForm = useForm({});

// Confirm delete modal
const confirmDelete = user => {
    userToDelete.value = user;
    deleteModal.value = true;
};

// Delete a user
const deleteUser = () => {
    deleteForm.delete(route('admin.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            deleteModal.value = false;
            userToDelete.value = null;
        }
    });
};

// Approve a user
const approveUser = userId => {
    actionForm.post(route('admin.users.approve', userId));
};

// Impersonate a user
const impersonateUser = userId => {
    actionForm.post(route('admin.users.impersonate', userId));
};

// Copy API key to clipboard
const copyApiKey = async apiKey => {
    try {
        await navigator.clipboard.writeText(apiKey);
        alert('API key copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy API key:', err);
        alert('Failed to copy API key to clipboard');
    }
};
</script>
