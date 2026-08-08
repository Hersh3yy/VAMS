<script setup lang="ts">
import BaseButton from '@/Components/Base/Button.vue';
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
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-secondary focus:ring-secondary dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-secondary dark:focus:ring-secondary"
                    v-model="form.password"
                    placeholder="Password"
                />

                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.password }}
                </p>
            </div>
        </div>

        <div class="flex justify-end">
            <BaseButton
                variant="danger"
                :disabled="form.processing"
                :loading="form.processing"
                @click="confirmUserDeletion"
            >
                Delete Account
            </BaseButton>
        </div>
    </section>
</template>
