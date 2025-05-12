<!-- components/ImageModal.vue -->
<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity" @click="$emit('close')">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div class="relative inline-block bg-white rounded-lg overflow-hidden shadow-xl transform transition-all max-w-4xl w-full">
                <div class="absolute top-0 right-0 pt-4 pr-4 z-50">
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="bg-white p-6">
                    <img :src="image.path" :alt="image.title || 'Image'" class="w-full h-auto max-h-[60vh] object-contain">
                    <div class="mt-4 space-y-4">
                        <div v-if="displaySettings?.title !== false">
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" v-model="formData.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div v-if="displaySettings?.altText !== false">
                            <label class="block text-sm font-medium text-gray-700">Alt Text</label>
                            <input type="text" v-model="formData.altText" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div v-if="displaySettings?.caption !== false">
                            <label class="block text-sm font-medium text-gray-700">Caption</label>
                            <textarea v-model="formData.caption" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                        <div v-if="displaySettings?.dateCreated !== false">
                            <label class="block text-sm font-medium text-gray-700">Date Created</label>
                            <input type="datetime-local" v-model="formData.dateCreated" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div v-if="displaySettings?.location !== false">
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" v-model="formData.location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div v-if="displaySettings?.tags !== false">
                            <label class="block text-sm font-medium text-gray-700">Tags</label>
                            <input type="text" v-model="formData.tags" placeholder="Separate tags with commas" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div v-if="displaySettings?.author !== false">
                            <label class="block text-sm font-medium text-gray-700">Author</label>
                            <input type="text" v-model="formData.author" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button @click="$emit('close')" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button @click="saveChanges" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

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
            author: true,
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
});

watch(() => props.image, (newImage) => {
    if (newImage) {
        formData.value = {
            title: newImage.title || '',
            altText: newImage.altText || '',
            caption: newImage.caption || '',
            dateCreated: newImage.dateCreated || '',
            location: newImage.location || '',
            tags: newImage.tags || '',
            author: newImage.author || '',
        };
    }
}, { immediate: true });

const saveChanges = async () => {
    try {
        await router.put(route('album-images.update', props.image.id), formData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                emit('update', { ...props.image, ...formData.value });
                emit('close');
            }
        });
    } catch (error) {
        console.error('Failed to update image:', error);
    }
};
</script>