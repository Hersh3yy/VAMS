<template>
    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg dark:bg-gray-800">
        <section>
            <header>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Custom Logo</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Upload your own logo to display in the navigation bar.
                </p>
            </header>

            <form @submit.prevent="updateLogo" class="mt-6 space-y-6">
                <div class="flex items-start space-x-6">
                    <div class="shrink-0">
                        <img 
                            v-if="user.logo_url" 
                            :src="user.logo_url" 
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
                            <button 
                                type="button" 
                                @click="logoInput?.click()"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                Select Logo
                            </button>
                            <button 
                                v-if="logo" 
                                type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                :disabled="form.processing"
                            >
                                Upload
                            </button>
                            <button
                                v-if="user.logo_url"
                                type="button"
                                @click="removeLogo"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                :disabled="removeLogoForm.processing"
                            >
                                Remove Logo
                            </button>
                        </div>
                        <div v-if="logo" class="mt-2 text-xs text-gray-500">
                            Selected: {{ logo.name }}
                        </div>
                        <p v-if="form.errors.logo" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.logo }}
                        </p>
                    </div>
                </div>
            </form>
        </section>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

interface User {
    id: number;
    name: string;
    email: string;
    logo_url?: string;
}

// Logo upload handling
const logoInput = ref<HTMLInputElement | null>(null);
const logo = ref<File | null>(null);
const form = useForm({
    logo: null as File | null,
});
const removeLogoForm = useForm({});

const user = usePage().props.auth?.user as unknown as User;

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
</script> 