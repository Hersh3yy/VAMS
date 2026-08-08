<template>
    <div class="bg-white p-4 shadow dark:bg-gray-800 sm:rounded-lg sm:p-8">
        <section>
            <header>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Album Display Settings
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Configure which details are shown when viewing album images.
                </p>
            </header>

            <form @submit.prevent="updateSettings" class="mt-6 space-y-6">
                <!-- Theme Colors -->
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Theme Colors
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Choose the main and accent colors used throughout your account.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex items-center gap-3">
                            <input
                                id="main_color_setting"
                                type="color"
                                v-model="settingsForm['album_display_settings.main_color']"
                                class="h-10 w-14 shrink-0 cursor-pointer rounded border border-gray-300 bg-transparent p-0.5 dark:border-gray-600"
                            />
                            <div>
                                <label
                                    for="main_color_setting"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Main Color
                                </label>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ settingsForm['album_display_settings.main_color'] }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input
                                id="secondary_color_setting"
                                type="color"
                                v-model="settingsForm['album_display_settings.secondary_color']"
                                class="h-10 w-14 shrink-0 cursor-pointer rounded border border-gray-300 bg-transparent p-0.5 dark:border-gray-600"
                            />
                            <div>
                                <label
                                    for="secondary_color_setting"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Secondary Color
                                </label>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ settingsForm['album_display_settings.secondary_color'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-center gap-3 rounded-md border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <span class="text-xs text-gray-500 dark:text-gray-400">Preview:</span>
                        <span
                            class="rounded-md px-3 py-1.5 text-sm font-medium"
                            :style="{
                                backgroundColor: settingsForm['album_display_settings.secondary_color'],
                                color: settingsForm['album_display_settings.main_color']
                            }"
                        >
                            Sample Button
                        </span>
                    </div>
                </div>

                <!-- Visible Fields -->
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Visible Fields
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Configure which details are shown when viewing album images.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex items-center">
                            <input
                                id="title_setting"
                                type="checkbox"
                                v-model="settingsForm['album_display_settings.title']"
                                class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label
                                for="title_setting"
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                Show Title
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input
                                id="author_setting"
                                type="checkbox"
                                v-model="settingsForm['album_display_settings.author']"
                                class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label
                                for="author_setting"
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                Show Author
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input
                                id="caption_setting"
                                type="checkbox"
                                v-model="settingsForm['album_display_settings.caption']"
                                class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label
                                for="caption_setting"
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                Show Caption
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input
                                id="altText_setting"
                                type="checkbox"
                                v-model="settingsForm['album_display_settings.altText']"
                                class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label
                                for="altText_setting"
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                Show Alt Text
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input
                                id="dateCreated_setting"
                                type="checkbox"
                                v-model="settingsForm['album_display_settings.dateCreated']"
                                class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label
                                for="dateCreated_setting"
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                Show Date Created
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input
                                id="location_setting"
                                type="checkbox"
                                v-model="settingsForm['album_display_settings.location']"
                                class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label
                                for="location_setting"
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                Show Location
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input
                                id="tags_setting"
                                type="checkbox"
                                v-model="settingsForm['album_display_settings.tags']"
                                class="rounded border-gray-300 text-secondary shadow-sm focus:ring-secondary dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label
                                for="tags_setting"
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                Show Tags
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-4">
                    <div
                        v-show="settingsForm.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Saved.
                    </div>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:focus:ring-offset-gray-800 dark:active:bg-gray-300"
                        :disabled="settingsForm.processing"
                    >
                        Save Display Settings
                    </button>
                </div>
            </form>
        </section>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface AlbumDisplaySettings {
    caption: boolean;
    altText: boolean;
    dateCreated: boolean;
    location: boolean;
    tags: boolean;
    title: boolean;
    author: boolean;
    main_color: string;
    secondary_color: string;
}

const props = defineProps<{
    albumDisplaySettings: AlbumDisplaySettings;
}>();

// Initialize form with props data (this was the bug - was using user object instead of props)
const settingsForm = useForm({
    'album_display_settings.main_color': props.albumDisplaySettings.main_color,
    'album_display_settings.secondary_color': props.albumDisplaySettings.secondary_color,
    'album_display_settings.caption': props.albumDisplaySettings.caption,
    'album_display_settings.altText': props.albumDisplaySettings.altText,
    'album_display_settings.dateCreated': props.albumDisplaySettings.dateCreated,
    'album_display_settings.location': props.albumDisplaySettings.location,
    'album_display_settings.tags': props.albumDisplaySettings.tags,
    'album_display_settings.title': props.albumDisplaySettings.title,
    'album_display_settings.author': props.albumDisplaySettings.author
});

const updateSettings = () => {
    // The updated album_display_settings come back via Inertia's shared auth.user
    // prop, so useUserTheme picks up the new colors reactively without a reload.
    settingsForm.patch(route('profile.theme.update'), {
        preserveScroll: true
    });
};
</script>
