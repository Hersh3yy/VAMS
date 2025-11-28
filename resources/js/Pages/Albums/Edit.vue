<template>
    <Head title="Edit Album" />

    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <div class="flex items-center">
                    <BackLink :href="route('albums.show', Album.id)" />
                    <h2 class="page-title">Edit Album</h2>
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
                                :upload-preview="coverImagePreview"
                                :error="form.errors.cover_image || form.errors.selected_cover_image_id"
                                @file-change="handleFileChange"
                                @select-image="selectCoverImage"
                                @clear-selection="clearSelectedCoverImage"
                                @clear-upload="clearUpload"
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    Album: Album;
}>();

const form = useForm({
    title: props.Album.title,
    description: props.Album.description || '',
    cover_image: null as File | null,
    selected_cover_image_id: null as string | null,
    published: props.Album.published !== undefined ? props.Album.published : true
});

const coverImagePreview = ref<string | null>(null);

const selectCoverImage = (image: AlbumImage) => {
    form.selected_cover_image_id = image.id;
    // Clear any uploaded cover image when selecting from album
    form.cover_image = null;
    coverImagePreview.value = null;
};

const clearSelectedCoverImage = () => {
    form.selected_cover_image_id = null;
};

const clearUpload = () => {
    form.cover_image = null;
    coverImagePreview.value = null;
    // Reset file input
    const fileInput = document.getElementById('cover_image') as HTMLInputElement;
    if (fileInput) {
        fileInput.value = '';
    }
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.cover_image = file;
        // Clear selection when uploading
        form.selected_cover_image_id = null;
        // Create preview URL
        const reader = new FileReader();
        reader.onload = (e: ProgressEvent<FileReader>) => {
            if (e.target?.result) {
                coverImagePreview.value = e.target.result as string;
            }
        };
        reader.readAsDataURL(file);
    } else {
        form.cover_image = null;
        coverImagePreview.value = null;
    }
};

const submit = () => {
    form.patch(route('albums.update', props.Album.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Reset the file input and preview
            form.cover_image = null;
            coverImagePreview.value = null;
            form.selected_cover_image_id = null;
        },
        onError: errors => {
            console.error('Form submission errors:', errors);
        }
    });
};
</script>
