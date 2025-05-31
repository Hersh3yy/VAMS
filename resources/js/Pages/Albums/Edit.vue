<template>
    <Head title="Edit Album" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <Link :href="route('albums.show', album.id)" 
                        class="mr-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-full inline-flex items-center transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Album</h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="title" class="form-label">Title *</label>
                            <input
                                id="title"
                                type="text"
                                class="form-input"
                                v-model="form.title"
                                required
                                autofocus
                                placeholder="Enter album title"
                            />
                            <div v-if="form.errors.title" class="form-error">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                id="description"
                                class="form-input"
                                v-model="form.description"
                                rows="4"
                                placeholder="Enter album description"
                            ></textarea>
                            <div v-if="form.errors.description" class="form-error">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div>
                            <label for="cover_image" class="form-label">Cover Image</label>
                            <div class="mt-2 space-y-4">
                                <!-- Current and New Image Display -->
                                <div class="flex items-start space-x-4">
                                    <!-- Current Cover Image -->
                                    <div v-if="album.cover_image_path && !coverImagePreview" class="flex-shrink-0">
                                        <div class="text-sm text-gray-600 mb-2">Current cover:</div>
                                        <img 
                                            :src="album.cover_image_path" 
                                            class="h-32 w-32 object-cover rounded-lg border-2 border-gray-200"
                                            alt="Current cover"
                                        />
                                    </div>
                                    
                                    <!-- New Image Preview -->
                                    <div v-if="coverImagePreview" class="flex-shrink-0">
                                        <div class="text-sm text-gray-600 mb-2">New cover preview:</div>
                                        <img 
                                            :src="coverImagePreview" 
                                            class="h-32 w-32 object-cover rounded-lg border-2 border-green-200"
                                            alt="New cover preview"
                                        />
                                    </div>
                                    
                                    <!-- File Input -->
                                    <div class="flex-1">
                                        <input 
                                            type="file" 
                                            id="cover_image"
                                            @change="handleFileChange"
                                            class="form-input"
                                            accept="image/*"
                                        />
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ album.cover_image_path ? 'Choose a new image to replace the current cover.' : 'Choose an image for the album cover.' }}
                                            PNG, JPG, GIF up to 10MB.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.cover_image" class="form-error">
                                {{ form.errors.cover_image }}
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-3 mt-6">
                            <Link 
                                :href="route('albums.show', album.id)"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                class="btn-primary"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Album</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    album: Object,
});

const form = useForm({
    title: props.album.title,
    description: props.album.description || '',
    cover_image: null,
    _method: 'PUT'
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
    form.post(route('albums.update', props.album.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset the file input and preview
            form.cover_image = null;
            coverImagePreview.value = null;
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        }
    });
};
</script> 