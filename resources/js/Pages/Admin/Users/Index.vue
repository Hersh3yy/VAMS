<template>
    <Head title="Manage Users" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Users</h2>
                <div>
                    <Link :href="route('admin.users.create')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        Add New User
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.flash.error }}</span>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Filter Options -->
                        <div class="mb-4 flex justify-between">
                            <div class="flex space-x-2">
                                <button 
                                    @click="activeFilter = 'all'"
                                    :class="['px-3 py-1 rounded', activeFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-200']"
                                >
                                    All Users
                                </button>
                                <button 
                                    @click="activeFilter = 'admins'"
                                    :class="['px-3 py-1 rounded', activeFilter === 'admins' ? 'bg-primary text-white' : 'bg-gray-200']"
                                >
                                    Admins
                                </button>
                                <button 
                                    @click="activeFilter = 'pending'"
                                    :class="['px-3 py-1 rounded', activeFilter === 'pending' ? 'bg-primary text-white' : 'bg-gray-200']"
                                >
                                    Pending Approval
                                </button>
                            </div>
                            <div>
                                <input 
                                    v-model="search" 
                                    type="text" 
                                    placeholder="Search users..." 
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                            </div>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Albums
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in filteredUsers" :key="user.id" :class="{'bg-yellow-50': !user.is_approved}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="user.is_admin" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                Admin
                                            </span>
                                            <span v-if="user.is_approved" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Approved
                                            </span>
                                            <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pending
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ user.albums_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-2">
                                            <Link :href="route('admin.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                                Edit
                                            </Link>
                                            
                                            <button 
                                                v-if="!user.is_approved"
                                                @click="approveUser(user.id)" 
                                                class="text-green-600 hover:text-green-900 mr-2"
                                            >
                                                Approve
                                            </button>
                                            
                                            <button 
                                                @click="impersonateUser(user.id)"
                                                class="text-blue-600 hover:text-blue-900 mr-2"
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
                    Are you sure you want to delete {{ userToDelete?.name }}? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="deleteModal = false" class="mr-3">
                        Cancel
                    </SecondaryButton>
                    <button @click="deleteUser" class="btn-danger">
                        Delete User
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/general/Modal.vue';
import SecondaryButton from '@/Components/general/SecondaryButton.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Array,
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
        result = result.filter(user => 
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
const formatDate = (dateString) => {
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
const confirmDelete = (user) => {
    userToDelete.value = user;
    deleteModal.value = true;
};

// Delete a user
const deleteUser = () => {
    deleteForm.delete(route('admin.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            deleteModal.value = false;
            userToDelete.value = null;
        },
    });
};

// Approve a user
const approveUser = (userId) => {
    actionForm.post(route('admin.users.approve', userId));
};

// Impersonate a user
const impersonateUser = (userId) => {
    actionForm.post(route('admin.users.impersonate', userId));
};
</script> 