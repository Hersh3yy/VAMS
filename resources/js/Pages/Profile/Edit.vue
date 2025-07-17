<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import AlbumDisplaySettingsSection from './Partials/AlbumDisplaySettingsSection.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import LogoUploadSection from './Partials/LogoUploadSection.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    logo_url?: string;
    album_display_settings?: {
        main_color?: string;
        secondary_color?: string;
        caption?: boolean;
        altText?: boolean;
        dateCreated?: boolean;
        location?: boolean;
        tags?: boolean;
        title?: boolean;
        author?: boolean;
    };
}

defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
    album_display_settings: {
        caption: boolean;
        altText: boolean;
        dateCreated: boolean;
        location: boolean;
        tags: boolean;
        title: boolean;
        author: boolean;
        main_color: string;
        secondary_color: string;
    };
}>();
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Logo Upload Section -->
                <LogoUploadSection />

                <!-- Album Display Settings Section -->
                <AlbumDisplaySettingsSection :album-display-settings="album_display_settings" />

                <div class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
