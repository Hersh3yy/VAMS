<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    title: '',
    description: '',
    cover_image: null,
});

const coverImagePreview = ref(null);

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.cover_image = file;
        // Create preview URL
        const reader = new FileReader();
        reader.onload = (e) => {
            coverImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.cover_image = null;
        coverImagePreview.value = null;
    }
};

const submit = () => {
    form.post(route('albums.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Form will be redirected to show page after successful creation
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};
</script>

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
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-800"
                    >
                        Create Album
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="form-label">Title *</label>
                                <input
                                    type="text"
                                    v-model="form.title"
                                    required
                                    class="form-input"
                                    placeholder="Enter album title"
                                />
                                <div
                                    v-if="form.errors.title"
                                    class="form-error"
                                >
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="4"
                                    class="form-input"
                                    placeholder="Enter album description"
                                ></textarea>
                                <div
                                    v-if="form.errors.description"
                                    class="form-error"
                                >
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Cover Image</label>
                                <div class="flex items-start space-x-4">
                                    <!-- Image Preview -->
                                    <div
                                        v-if="coverImagePreview"
                                        class="flex-shrink-0"
                                    >
                                        <img
                                            :src="coverImagePreview"
                                            class="h-32 w-32 rounded-lg border-2 border-gray-200 object-cover"
                                            alt="Cover image preview"
                                        />
                                    </div>

                                    <!-- File Input -->
                                    <div class="flex-1">
                                        <input
                                            type="file"
                                            @change="handleFileChange"
                                            class="form-input"
                                            accept="image/*"
                                        />
                                        <p class="mt-1 text-sm text-gray-500">
                                            Choose an image to represent this
                                            album. PNG, JPG, GIF up to 10MB.
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="form.errors.cover_image"
                                    class="form-error"
                                >
                                    {{ form.errors.cover_image }}
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <Link
                                    :href="route('albums.index')"
                                    class="btn-danger"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    class="btn-primary"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing"
                                        >Creating...</span
                                    >
                                    <span v-else>Create Album</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
