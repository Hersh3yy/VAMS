<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-gray-900 sm:justify-center sm:pt-0"
    >
        <main
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md dark:bg-gray-800 sm:max-w-md sm:rounded-lg"
        >
            <h1 class="mb-4 text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Log in
            </h1>

            <!-- Status Message -->
            <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <!-- Flash Error (e.g. pending account approval) -->
            <div
                v-if="$page.props.flash?.error"
                class="relative mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/50 dark:text-red-300"
                role="alert"
            >
                {{ $page.props.flash.error }}
            </div>

            <!-- Flash Success (e.g. registration submitted, pending approval) -->
            <div
                v-if="$page.props.flash?.success"
                class="relative mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/50 dark:text-green-300"
                role="alert"
            >
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submit">
                <FormField
                    id="email"
                    label="Email"
                    type="email"
                    v-model="form.email"
                    :required="true"
                    :autofocus="true"
                    autocomplete="username"
                    :error="form.errors.email"
                />

                <PasswordField
                    id="password"
                    label="Password"
                    v-model="form.password"
                    :required="true"
                    autocomplete="current-password"
                    :error="form.errors.password"
                />

                <div class="mt-4 block">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            v-model="form.remember"
                        />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Remember me
                        </span>
                    </label>
                </div>

                <div class="mt-4 flex items-center justify-end">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="rounded-md text-sm text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        Forgot your password?
                    </Link>

                    <button
                        class="ml-4 inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:focus:ring-offset-gray-800 dark:active:bg-gray-300"
                        :disabled="form.processing"
                    >
                        Log in
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>

<script setup lang="ts">
import FormField from '@/Components/molecules/FormField.vue';
import PasswordField from '@/Components/molecules/PasswordField.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password')
    });
};
</script>
