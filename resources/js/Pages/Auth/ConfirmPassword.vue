<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-gray-900 sm:justify-center sm:pt-0"
    >
        <main
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md dark:bg-gray-800 sm:max-w-md sm:rounded-lg"
        >
            <h1 class="mb-4 text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Confirm Password
            </h1>

            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                This is a secure area of the application. Please confirm your password before
                continuing.
            </div>

            <form @submit.prevent="submit">
                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-secondary focus:ring-secondary dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-secondary dark:focus:ring-secondary"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />

                    <p
                        v-if="form.errors.password"
                        class="mt-2 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="mt-4 flex items-center justify-end">
                    <button
                        class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:focus:ring-offset-gray-800 dark:active:bg-gray-300"
                        :disabled="form.processing"
                    >
                        Confirm
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    password: ''
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset()
    });
};
</script>
