<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    album_display_settings: user.album_display_settings || {
        caption: true,
        altText: true,
        dateCreated: true,
        location: true,
        tags: true,
        title: true,
        author: true,
    },
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Album Image Display Settings
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input
                            id="title"
                            type="checkbox"
                            v-model="form.album_display_settings.title"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <label for="title" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Show Title
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input
                            id="author"
                            type="checkbox"
                            v-model="form.album_display_settings.author"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <label for="author" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Show Author
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input
                            id="caption"
                            type="checkbox"
                            v-model="form.album_display_settings.caption"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <label for="caption" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Show Caption
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input
                            id="altText"
                            type="checkbox"
                            v-model="form.album_display_settings.altText"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <label for="altText" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Show Alt Text
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input
                            id="dateCreated"
                            type="checkbox"
                            v-model="form.album_display_settings.dateCreated"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <label for="dateCreated" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Show Date Created
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input
                            id="location"
                            type="checkbox"
                            v-model="form.album_display_settings.location"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <label for="location" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Show Location
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input
                            id="tags"
                            type="checkbox"
                            v-model="form.album_display_settings.tags"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <label for="tags" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Show Tags
                        </label>
                    </div>
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
