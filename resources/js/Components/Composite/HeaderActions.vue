<template>
    <div class="flex space-x-3">
        <Button variant="danger" @click="$emit('delete')">
            Delete {{ entityType }}
        </Button>

        <Button variant="secondary" as="a" :href="editUrl">
            Edit {{ entityType }}
        </Button>

        <Button variant="primary" as="label" class="cursor-pointer">
            Add Images
            <input
                type="file"
                multiple
                @change="$emit('upload', $event)"
                accept="image/*"
                class="hidden"
            >
        </Button>

        <Button variant="primary" @click="$emit('add-video')">
            <Icon name="video" class="mr-2" />
            Add Video
        </Button>
    </div>
</template>

<script setup lang="ts">
import Button from '@/Components/Base/Button.vue';
import Icon from '@/Components/Base/Icon.vue';

interface Props {
    entityType: string;
    editUrl: string;
}

defineProps<Props>();

defineEmits<{
    (e: 'delete'): void;
    (e: 'upload', event: Event): void;
    (e: 'add-video'): void;
}>();
</script>
