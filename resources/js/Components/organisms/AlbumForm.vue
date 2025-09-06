<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <!-- Title Field -->
        <div>
            <label
                for="album-title"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                Title *
            </label>
            <Input
                id="album-title"
                :model-value="title"
                type="text"
                required
                placeholder="Enter album title"
                :error="errors.title"
                @update:model-value="$emit('update:title', $event)"
            />
        </div>

        <!-- Description Field -->
        <div>
            <label
                for="album-description"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                Description
            </label>
            <Textarea
                id="album-description"
                :model-value="description"
                :rows="4"
                placeholder="Enter album description"
                :error="errors.description"
                @update:model-value="$emit('update:description', $event)"
            />
        </div>

        <!-- Cover Image Section -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Cover Image
            </label>

            <!-- File Upload -->
            <div class="mt-2">
                <Button variant="secondary" as="label" class="cursor-pointer">
                    Choose Cover Image
                    <input
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="$emit('cover-file-change', $event)"
                    >
                </Button>
            </div>

            <!-- Cover Image Preview -->
            <div v-if="coverImagePreview" class="mt-4">
                <div class="relative inline-block">
                    <img
                        :src="coverImagePreview"
                        alt="Cover preview"
                        class="h-40 w-60 rounded-lg object-cover"
                    >
                    <Button
                        variant="danger"
                        size="sm"
                        icon-only
                        class="absolute right-2 top-2"
                        @click="$emit('clear-cover-preview')"
                    >
                        <Icon name="x" size="sm" />
                    </Button>
                </div>
            </div>

            <!-- Selected Album Image as Cover -->
            <div v-if="selectedCoverImage" class="mt-4">
                <div class="relative inline-block">
                    <img
                        :src="getImageUrl(selectedCoverImage)"
                        alt="Selected cover"
                        class="h-40 w-60 rounded-lg object-cover"
                    >
                    <Button
                        variant="danger"
                        size="sm"
                        icon-only
                        class="absolute right-2 top-2"
                        @click="$emit('clear-selected-cover')"
                    >
                        <Icon name="x" size="sm" />
                    </Button>
                </div>
            </div>

            <!-- Select from Album Button (only in edit mode) -->
            <div v-if="isEditMode && albumImages?.length" class="mt-2">
                <Button variant="secondary" @click="$emit('select-from-album')">
                    Select from Album Images
                </Button>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-4">
            <Button variant="secondary" type="button" @click="$emit('cancel')"> Cancel </Button>
            <Button variant="primary" type="submit" :loading="loading">
                {{ isEditMode ? 'Update Album' : 'Create Album' }}
            </Button>
        </div>
    </form>
</template>

<script setup lang="ts">
import Button from '@/Components/Base/Button.vue';
import Icon from '@/Components/Base/Icon.vue';
import Input from '@/Components/Base/Input.vue';
import Textarea from '@/Components/Base/Textarea.vue';

interface Props {
    title: string;
    description: string;
    coverImagePreview?: string | null;
    selectedCoverImage?: any;
    albumImages?: any[];
    errors: Record<string, string>;
    loading?: boolean;
    isEditMode?: boolean;
}

withDefaults(defineProps<Props>(), {
    coverImagePreview: null,
    selectedCoverImage: null,
    albumImages: () => [],
    loading: false,
    isEditMode: false
});

defineEmits<{
    'update:title': [value: string];
    'update:description': [value: string];
    'cover-file-change': [event: Event];
    'clear-cover-preview': [];
    'clear-selected-cover': [];
    'select-from-album': [];
    submit: [];
    cancel: [];
}>();

// Helper function to get image URL (could be moved to a utility if reused)
const getImageUrl = (image: any) => {
    if (!image) return '/images/placeholder.svg';

    // Handle video items
    if (image.properties) {
        const properties =
            typeof image.properties === 'string' ? JSON.parse(image.properties) : image.properties;

        if (properties?.type === 'video' && properties?.thumbnail_url) {
            return properties.thumbnail_url;
        }
    }

    return image.path || '/images/placeholder.svg';
};
</script>
