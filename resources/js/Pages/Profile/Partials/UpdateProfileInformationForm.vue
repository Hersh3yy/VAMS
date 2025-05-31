<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
}>();

const form = useForm({
    name: '',
    email: '',
});

const updateProfileInformation = () => {
    form.put(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Profile Information</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="updateProfileInformation" class="mt-6 space-y-6">
            <div>
                <label for="name" class="form-label">
                    Name
                </label>

                <input
                    id="name"
                    type="text"
                    class="form-input"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <p v-if="form.errors.name" class="form-error">
                    {{ form.errors.name }}
                </p>
            </div>

            <div>
                <label for="email" class="form-label">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    class="form-input"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <p v-if="form.errors.email" class="form-error">
                    {{ form.errors.email }}
                </p>
            </div>

            <div v-if="props.mustVerifyEmail && props.status === 'verification-link-sent'">
                <div class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button
                    class="btn-primary"
                    :disabled="form.processing"
                >
                    Save
                </button>

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
