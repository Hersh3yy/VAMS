<template>
    <Head title="Edit Album" />

    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <div class="flex items-center">
                    <BackLink :href="route('albums.show', Album.id)" />
                    <h1 class="page-title">Edit Album</h1>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <div class="card">
                    <div class="card-content">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Album Title -->
                            <div>
                                <label
                                    for="title"
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Title *
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    v-model="form.title"
                                    required
                                    autofocus
                                    placeholder="Enter album title"
                                />
                                <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <!-- Album Description -->
                            <div>
                                <label
                                    for="description"
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Enter album description"
                                />
                                <div
                                    v-if="form.errors.description"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <!-- Published Toggle -->
                            <Checkbox
                                id="published"
                                v-model="form.published"
                                label="Published"
                                hint="Published albums are visible in the API"
                            />

                            <!-- Cover Image Selector -->
                            <CoverImageSelector
                                :current-cover-url="Album.cover_image_path"
                                :album-images="Album.images"
                                :selected-image-id="form.selected_cover_image_id"
                                :error="form.errors.selected_cover_image_id"
                                @select-image="selectCoverImage"
                                @clear-selection="clearSelectedCoverImage"
                            />

                            <!-- Form Actions -->
                            <div
                                class="flex items-center justify-end space-x-3 border-t border-gray-200 pt-6 dark:border-gray-700"
                            >
                                <Link
                                    :href="route('albums.show', Album.id)"
                                    class="btn btn-secondary"
                                >
                                    Cancel
                                </Link>
                                <BaseButton
                                    type="submit"
                                    :disabled="form.processing"
                                    :loading="form.processing"
                                >
                                    Update Album
                                </BaseButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import CoverImageSelector from '@/Components/albums/CoverImageSelector.vue';
import Checkbox from '@/Components/atoms/Checkbox.vue';
import BackLink from '@/Components/Base/BackLink.vue';
import BaseButton from '@/Components/Base/Button.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Album, AlbumImage } from '@/types/album';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    Album: Album;
}>();

const form = useForm({
    title: props.Album.title,
    description: props.Album.description || '',
    selected_cover_image_id: null as string | null,
    published: props.Album.published !== undefined ? props.Album.published : true
});

const selectCoverImage = (image: AlbumImage) => {
    form.selected_cover_image_id = image.id;
};

const clearSelectedCoverImage = () => {
    form.selected_cover_image_id = null;
};

const submit = () => {
    form.patch(route('albums.update', props.Album.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.selected_cover_image_id = null;
        },
        onError: errors => {
            console.error('Form submission errors:', errors);
        }
    });
};
</script>
