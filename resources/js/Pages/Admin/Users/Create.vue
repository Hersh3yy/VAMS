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
                                <InputLabel for="name" value="Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="password" value="Password" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>

                            <div class="mb-4 flex space-x-4">
                                <div class="flex items-center">
                                    <Checkbox id="is_admin" v-model:checked="form.is_admin" />
                                    <InputLabel for="is_admin" value="Admin User" class="ml-2" />
                                    <InputError :message="form.errors.is_admin" class="mt-2" />
                                </div>

                                <div class="flex items-center">
                                    <Checkbox id="is_approved" v-model:checked="form.is_approved" />
                                    <InputLabel for="is_approved" value="Approved" class="ml-2" />
                                    <InputError :message="form.errors.is_approved" class="mt-2" />
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <PrimaryButton :disabled="form.processing" class="ml-4">
                                    Create User
                                </PrimaryButton>
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
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    is_admin: false,
    is_approved: true,
});
</script> 