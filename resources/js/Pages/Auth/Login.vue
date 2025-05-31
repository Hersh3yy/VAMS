<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <form @submit.prevent="submit">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="mt-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />

                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            v-model="form.remember"
                        />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                    >
                        Forgot your password?
                    </Link>

                    <button
                        class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        :disabled="form.processing"
                    >
                        Log in
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
