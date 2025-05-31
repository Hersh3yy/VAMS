<template>
    <Head title="Create User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New User</h2>
                <div>
                    <Link :href="route('admin.users.index')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Back to Users
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
                        <form @submit.prevent="form.post(route('admin.users.store'))">
                            <div class="mb-4">
                                <label for="name" class="form-label">Name</label>
                                <input
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    class="form-input"
                                />
                                <div v-if="form.errors.name" class="form-error">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    class="form-input"
                                />
                                <div v-if="form.errors.email" class="form-error">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    class="form-input"
                                />
                                <div v-if="form.errors.password" class="form-error">
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <div class="mb-4 flex space-x-4">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_admin"
                                        v-model="form.is_admin"
                                        class="form-checkbox"
                                    />
                                    <label for="is_admin" class="form-label ml-2">Admin User</label>
                                    <div v-if="form.errors.is_admin" class="form-error">
                                        {{ form.errors.is_admin }}
                                    </div>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_approved"
                                        v-model="form.is_approved"
                                        class="form-checkbox"
                                    />
                                    <label for="is_approved" class="form-label ml-2">Approved</label>
                                    <div v-if="form.errors.is_approved" class="form-error">
                                        {{ form.errors.is_approved }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button
                                    type="submit"
                                    class="btn-primary"
                                    :disabled="form.processing"
                                >
                                    Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    is_admin: false,
    is_approved: true,
});
</script> 