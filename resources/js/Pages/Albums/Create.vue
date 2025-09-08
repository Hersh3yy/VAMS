<template>
    <Head title="Create Album" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link
                        :href="route('albums.index')"
                        class="mr-4 inline-flex items-center rounded-full bg-gray-200 px-4 py-2 font-bold text-gray-800 transition-all duration-200 hover:bg-gray-300"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-1 h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        Back
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Create Album</h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <AlbumForm
                            :title="form.title"
                            :description="form.description"
                            :cover-image-preview="coverImagePreview"
                            :errors="form.errors"
                            :loading="form.processing"
                            @update:title="form.title = $event"
                            @update:description="form.description = $event"
                            @cover-file-change="handleFileChange"
                            @clear-cover-preview="clearCoverImage"
                            @submit="submit"
                            @cancel="() => {}"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AlbumForm from '@/Components/organisms/AlbumForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    title: '',
    description: '',
    cover_image: null
});

const coverImagePreview = ref(null);

const handleFileChange = event => {
    const file = event.target.files[0];
    if (file) {
        form.cover_image = file;
        // Create preview URL
        const reader = new FileReader();
        reader.onload = e => {
            coverImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.cover_image = null;
        coverImagePreview.value = null;
    }
};

const clearCoverImage = () => {
    form.cover_image = null;
    coverImagePreview.value = null;
};

const submit = () => {
    form.post(route('albums.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Form will be redirected to show page after successful creation
        },
        onError: errors => {
            console.error('Form submission errors:', errors);
        }
    });
};
</script>
