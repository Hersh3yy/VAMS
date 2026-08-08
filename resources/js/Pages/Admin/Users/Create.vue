<template>
    <Head title="Create User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold leading-tight text-gray-800">Create New User</h1>
                <div>
                    <Link
                        :href="route('admin.users.index')"
                        class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600"
                    >
                        Back to Users
                    </Link>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
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
                                    <label for="is_approved" class="form-label ml-2"
                                        >Approved</label
                                    >
                                    <div v-if="form.errors.is_approved" class="form-error">
                                        {{ form.errors.is_approved }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-end">
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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    is_admin: false,
    is_approved: true
});
</script>
