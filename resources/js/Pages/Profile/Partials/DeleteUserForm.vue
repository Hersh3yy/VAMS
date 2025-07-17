<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    password: ''
});

const confirmUserDeletion = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => form.reset(),
        onFinish: () => form.reset()
    });
};

const closeModal = () => {
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Delete Account</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Once your account is deleted, all of its resources and data will be permanently
                deleted. Before deleting your account, please download any data or information that
                you wish to retain.
            </p>
        </header>

        <div class="max-w-xl">
            <div class="mt-6">
                <label
                    for="password"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                    v-model="form.password"
                    placeholder="Password"
                />

                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.password }}
                </p>
            </div>
        </div>

        <div class="flex justify-end">
            <button
                class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700 dark:focus:ring-offset-gray-800"
                :disabled="form.processing"
                @click="confirmUserDeletion"
            >
                Delete Account
            </button>
        </div>
    </section>
</template>
