<template>
    <Head title="Create User" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Create New User">
                <template #actions>
                    <Link :href="route('admin.users.index')">
                        <BaseButton variant="secondary">Back to Users</BaseButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="mb-4 space-y-2">
                    <FlashMessage :message="page.props.flash?.success" type="success" />
                    <FlashMessage :message="page.props.flash?.error" type="error" />
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <form @submit.prevent="form.post(route('admin.users.store'))" class="p-6">
                        <FormField
                            id="name"
                            label="Name"
                            v-model="form.name"
                            :error="form.errors.name"
                            placeholder="Full name"
                            required
                            autofocus
                        />

                        <FormField
                            id="email"
                            label="Email"
                            type="email"
                            v-model="form.email"
                            :error="form.errors.email"
                            placeholder="user@example.com"
                            required
                        />

                        <PasswordField
                            id="password"
                            label="Password"
                            v-model="form.password"
                            :error="form.errors.password"
                            autocomplete="new-password"
                            required
                        />

                        <fieldset class="mb-6 mt-6">
                            <legend class="sr-only">User flags</legend>
                            <div class="flex flex-wrap gap-x-8 gap-y-3">
                                <Checkbox id="is_admin" label="Admin User" v-model="form.is_admin" />
                                <Checkbox id="is_approved" label="Approved" v-model="form.is_approved" />
                            </div>
                            <BaseErrorMessage :error="form.errors.is_admin" />
                            <BaseErrorMessage :error="form.errors.is_approved" />
                        </fieldset>

                        <div class="flex items-center justify-end gap-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <Link :href="route('admin.users.index')">
                                <BaseButton variant="secondary">Cancel</BaseButton>
                            </Link>
                            <BaseButton
                                type="submit"
                                variant="primary"
                                :disabled="form.processing"
                                :loading="form.processing"
                            >
                                Create User
                            </BaseButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import BaseButton from '@/Components/Base/Button.vue';
import BaseErrorMessage from '@/Components/Base/ErrorMessage.vue';
import FlashMessage from '@/Components/Base/FlashMessage.vue';
import Checkbox from '@/Components/atoms/Checkbox.vue';
import FormField from '@/Components/molecules/FormField.vue';
import PasswordField from '@/Components/molecules/PasswordField.vue';
import PageHeader from '@/Components/molecules/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<PageProps>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    is_admin: false,
    is_approved: true
});
</script>
