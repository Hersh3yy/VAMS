<script setup lang="ts">
import BaseButton from '@/Components/Base/Button.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        }
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Update Password</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Ensure your account is using a long, random password to stay secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <label for="current_password" class="block text-sm font-medium text-white">
                    Current Password
                </label>

                <input
                    id="current_password"
                    type="password"
                    class="form-input"
                    v-model="form.current_password"
                    autocomplete="current-password"
                />

                <p v-if="form.errors.current_password" class="form-error">
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-white">
                    New Password
                </label>

                <input
                    id="password"
                    type="password"
                    class="form-input"
                    v-model="form.password"
                    autocomplete="new-password"
                />

                <p v-if="form.errors.password" class="form-error">
                    {{ form.errors.password }}
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-white">
                    Confirm Password
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    class="form-input"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                />

                <p v-if="form.errors.password_confirmation" class="form-error">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <div class="flex items-center gap-4">
                <BaseButton type="submit" :disabled="form.processing" :loading="form.processing">
                    Save
                </BaseButton>

                <div
                    v-show="form.recentlySuccessful"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >
                    Saved.
                </div>
            </div>
        </form>
    </section>
</template>
