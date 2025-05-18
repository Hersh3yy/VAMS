<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref, reactive, computed } from 'vue';
import { User } from '@/types';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    album_display_settings: {
        caption: boolean;
        altText: boolean;
        dateCreated: boolean;
        location: boolean;
        tags: boolean;
    };
}>();

// Logo upload handling
const logoInput = ref<HTMLInputElement | null>(null);
const logo = ref<File | null>(null);
const form = useForm({
    logo: null as File | null,
});
const removeLogoForm = useForm({});

const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] || null;
    if (file) {
        logo.value = file;
        form.logo = file;
    }
};

const updateLogo = () => {
    if (form.logo) {
        form.post(route('profile.logo.update'), {
            preserveScroll: true,
            onSuccess: () => {
                logo.value = null;
                if (logoInput.value) {
                    logoInput.value.value = '';
                }
            },
        });
    }
};

const removeLogo = () => {
    removeLogoForm.delete(route('profile.logo.destroy'), {
        preserveScroll: true,
    });
};

// Theme settings
const user = usePage().props.auth.user as User;
const defaultMainColor = '#4F46E5'; // Default indigo color
const defaultSecondaryColor = '#10B981'; // Default emerald color

const themeForm = useForm({
    album_display_settings: {
        ...(user.album_display_settings || {}),
        main_color: user.album_display_settings?.main_color || defaultMainColor,
        secondary_color: user.album_display_settings?.secondary_color || defaultSecondaryColor,
    },
});

const updateTheme = () => {
    themeForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Force a page reload to apply theme changes
            window.location.reload();
        }
    });
};
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Logo Upload Section -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Custom Logo</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Upload your own logo to display in the navigation bar.
                            </p>
                        </header>

                        <form @submit.prevent="updateLogo" class="mt-6 space-y-6">
                            <div class="flex items-start space-x-6">
                                <div class="shrink-0">
                                    <img 
                                        v-if="$page.props.auth.user.logo_url" 
                                        :src="$page.props.auth.user.logo_url" 
                                        class="h-16 w-auto object-contain" 
                                        alt="Current logo" 
                                    />
                                    <div v-else class="h-16 w-16 bg-gray-100 flex items-center justify-center rounded">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input 
                                        type="file"
                                        ref="logoInput"
                                        @change="handleLogoChange"
                                        class="hidden"
                                        accept="image/*"
                                    >
                                    <div class="flex space-x-3">
                                        <PrimaryButton 
                                            type="button" 
                                            @click="logoInput?.click()"
                                            class="mt-2"
                                        >
                                            Select Logo
                                        </PrimaryButton>
                                        <PrimaryButton 
                                            v-if="logo" 
                                            type="submit" 
                                            class="mt-2"
                                            :class="{ 'opacity-25': form.processing }"
                                            :disabled="form.processing"
                                        >
                                            Upload
                                        </PrimaryButton>
                                        <DangerButton
                                            v-if="$page.props.auth.user.logo_url"
                                            type="button"
                                            @click="removeLogo"
                                            class="mt-2"
                                            :class="{ 'opacity-25': removeLogoForm.processing }"
                                            :disabled="removeLogoForm.processing"
                                        >
                                            Remove Logo
                                        </DangerButton>
                                    </div>
                                    <div v-if="logo" class="mt-2 text-xs text-gray-500">
                                        Selected: {{ logo.name }}
                                    </div>
                                    <InputError :message="form.errors.logo" class="mt-2" />
                                </div>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Theme Settings Section -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Theme Settings</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Customize the colors of your albums and galleries.
                            </p>
                        </header>

                        <form @submit.prevent="updateTheme" class="mt-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="main_color" value="Primary Color" />
                                    <div class="flex items-center space-x-3 mt-2">
                                        <input 
                                            id="main_color" 
                                            v-model="themeForm.album_display_settings.main_color" 
                                            type="color" 
                                            class="h-10 w-10 rounded cursor-pointer border-0"
                                        />
                                        <TextInput
                                            v-model="themeForm.album_display_settings.main_color"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                    <InputError :message="themeForm.errors['album_display_settings.main_color']" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="secondary_color" value="Secondary Color" />
                                    <div class="flex items-center space-x-3 mt-2">
                                        <input 
                                            id="secondary_color" 
                                            v-model="themeForm.album_display_settings.secondary_color" 
                                            type="color" 
                                            class="h-10 w-10 rounded cursor-pointer border-0"
                                        />
                                        <TextInput
                                            v-model="themeForm.album_display_settings.secondary_color"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                    <InputError :message="themeForm.errors['album_display_settings.secondary_color']" class="mt-2" />
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <PrimaryButton :class="{ 'opacity-25': themeForm.processing }" :disabled="themeForm.processing">
                                    Save Theme Settings
                                </PrimaryButton>
                            </div>
                        </form>
                    </section>
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :album-display-settings="album_display_settings"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
