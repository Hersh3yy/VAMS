<template>
    <div>
        <label
            for="cover_image"
            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            Cover Image
        </label>
        <div class="mt-2 space-y-4">
            <!-- Current and New Image Display -->
            <div class="flex items-start space-x-4">
                <!-- Current Cover Image -->
                <div v-if="album.cover_image_path && !coverImagePreview" class="flex-shrink-0">
                    <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">Current cover:</div>
                    <div
                        class="image-container h-32 w-32 overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700"
                    >
                        <img
                            :src="album.cover_image_path"
                            class="cover-image"
                            alt="Current cover"
                        />
                    </div>
                </div>

                <!-- New Image Preview -->
                <div v-if="coverImagePreview" class="flex-shrink-0">
                    <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                        New cover preview:
                    </div>
                    <div
                        class="image-container h-32 w-32 overflow-hidden rounded-lg border-2 border-secondary"
                    >
                        <img :src="coverImagePreview" class="cover-image" alt="New cover preview" />
                    </div>
                </div>

                <!-- File Input -->
                <div class="flex-1">
                    <input
                        type="file"
                        id="cover_image"
                        @change="$emit('file-change', $event)"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        accept="image/*"
                    />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{
                            album.cover_image_path
                                ? 'Choose a new image to replace the current cover.'
                                : 'Choose an image for the album cover.'
                        }}
                        PNG, JPG, GIF up to 10MB.
                    </p>
                </div>
            </div>
        </div>
        <div v-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </div>
    </div>
</template>

<script>
defineProps({
    album: {
        type: Object,
        required: true
    },
    coverImagePreview: {
        type: String,
        default: null
    },
    error: {
        type: String,
        default: null
    }
});

defineEmits(['file-change']);
</script>
