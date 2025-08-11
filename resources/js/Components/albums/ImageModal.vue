<!-- components/ImageModal.vue -->
<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div
            class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"
        >
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" />
            </div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true"
                >&#8203;</span
            >

            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6 sm:align-middle"
            >
                <div class="flex items-center justify-between border-b p-4">
                    <h3 class="text-lg font-medium">
                        {{ isVideo ? 'Edit Video' : 'Edit Image' }}
                    </h3>
                    <button
                        type="button"
                        class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="$emit('close')"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <!-- Video Display -->
                    <div v-if="isVideo" class="mb-4">
                        <div class="aspect-video overflow-hidden rounded-lg bg-gray-100">
                            <iframe
                                v-if="videoEmbedUrl"
                                :src="videoEmbedUrl"
                                class="h-full w-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            />
                            <div v-else class="flex h-full w-full items-center justify-center">
                                <div class="text-center">
                                    <svg
                                        class="mx-auto mb-2 h-16 w-16 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <p class="text-gray-500">Video Player</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Display -->
                    <div v-else class="mb-4">
                        <img
                            :src="image.path"
                            :alt="image.title || 'Image'"
                            class="h-auto max-h-[60vh] w-full object-contain"
                        >
                    </div>

                    <div class="mt-4 space-y-4">
                        <!-- Video-specific fields -->
                        <template v-if="isVideo">
                            <ImageFormField
                                v-model="formData.videoUrl"
                                label="Video URL"
                                type="url"
                                readonly
                            />
                            <ImageFormField
                                v-model="formData.title"
                                label="Title"
                                :display-settings="displaySettings?.title"
                            />
                            <ImageFormField
                                v-model="formData.caption"
                                label="Caption"
                                type="textarea"
                                :display-settings="displaySettings?.caption"
                            />
                        </template>

                        <!-- Image-specific fields -->
                        <template v-else>
                            <ImageFormField
                                v-model="formData.title"
                                label="Title"
                                :display-settings="displaySettings?.title"
                            />
                            <ImageFormField
                                v-model="formData.altText"
                                label="Alt Text"
                                :display-settings="displaySettings?.altText"
                            />
                            <ImageFormField
                                v-model="formData.caption"
                                label="Caption"
                                type="textarea"
                                :display-settings="displaySettings?.caption"
                            />
                            <ImageFormField
                                v-model="formData.dateCreated"
                                label="Date Created"
                                type="datetime-local"
                                :display-settings="displaySettings?.dateCreated"
                            />
                            <ImageFormField
                                v-model="formData.location"
                                label="Location"
                                :display-settings="displaySettings?.location"
                            />
                            <ImageFormField
                                v-model="formData.tags"
                                label="Tags"
                                placeholder="Separate tags with commas"
                                :display-settings="displaySettings?.tags"
                            />
                            <ImageFormField
                                v-model="formData.author"
                                label="Author"
                                :display-settings="displaySettings?.author"
                            />
                        </template>

                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button
                                type="button"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                @click="saveChanges"
                            >
                                Save Changes
                            </button>
                            <button
                                type="button"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ImageFormField } from '@/Components/Composite';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    image: {
        type: Object,
        required: true
    },
    displaySettings: {
        type: Object,
        default: () => ({
            title: true,
            caption: true,
            altText: true,
            dateCreated: true,
            location: true,
            tags: true,
            author: true
        })
    }
});

const emit = defineEmits(['close', 'update']);

const formData = ref({
    title: '',
    altText: '',
    caption: '',
    dateCreated: '',
    location: '',
    tags: '',
    author: '',
    videoUrl: ''
});

const isVideo = computed(() => {
    if (!props.image?.properties) return false;

    // Handle both string and object properties
    const properties =
        typeof props.image.properties === 'string'
            ? JSON.parse(props.image.properties)
            : props.image.properties;

    return properties?.type === 'video';
});

const videoEmbedUrl = computed(() => {
    if (!isVideo.value) return null;

    // Handle both string and object properties
    const properties =
        typeof props.image.properties === 'string'
            ? JSON.parse(props.image.properties)
            : props.image.properties;

    const url = properties?.video_url || props.image?.path || '';

    // Convert to embed URL for YouTube/Vimeo
    if (url.includes('youtube.com/watch')) {
        const videoId = url.split('v=')[1]?.split('&')[0];
        return `https://www.youtube.com/embed/${videoId}`;
    } else if (url.includes('youtu.be/')) {
        const videoId = url.split('youtu.be/')[1]?.split('?')[0];
        return `https://www.youtube.com/embed/${videoId}`;
    } else if (url.includes('vimeo.com/')) {
        const videoId = url.split('vimeo.com/')[1]?.split('?')[0];
        return `https://player.vimeo.com/video/${videoId}`;
    }

    return null;
});

watch(
    () => props.image,
    newImage => {
        if (newImage) {
            // Handle both string and object properties
            const properties =
                typeof newImage.properties === 'string'
                    ? JSON.parse(newImage.properties)
                    : newImage.properties;

            formData.value = {
                title: newImage.title || '',
                altText: newImage.altText || '',
                caption: newImage.caption || '',
                dateCreated: newImage.dateCreated || '',
                location: newImage.location || '',
                tags: newImage.tags || '',
                author: newImage.author || '',
                videoUrl: properties?.video_url || newImage.path || ''
            };
        }
    },
    { immediate: true }
);

const saveChanges = async () => {
    try {
        const updateData = isVideo.value
            ? {
                  title: formData.value.title,
                  caption: formData.value.caption
              }
            : {
                  title: formData.value.title,
                  altText: formData.value.altText,
                  caption: formData.value.caption,
                  dateCreated: formData.value.dateCreated,
                  location: formData.value.location,
                  tags: formData.value.tags,
                  author: formData.value.author
              };

        await router.patch(
            route('albums.images.update', {
                album: props.image.album_id,
                image: props.image.id
            }),
            updateData,
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    emit('update', { ...props.image, ...updateData });
                    emit('close');
                },
                onError: errors => {
                    console.error('Album image update error:', errors);
                    // Handle error with user-friendly message
                    alert('Unable to save changes. Please check the fields and try again.');
                }
            }
        );
    } catch (error) {
        // Show user-friendly error message
        alert('Unable to save changes. Please try again.');
    }
};
</script>
