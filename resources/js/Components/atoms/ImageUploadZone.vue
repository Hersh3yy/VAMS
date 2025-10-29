<template>
    <div 
        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center transition-colors"
        :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
    >
        <input
            type="file"
            :multiple="multiple"
            :accept="accept"
            @change="handleFileSelect"
            class="hidden"
            :id="inputId"
            ref="fileInput"
        />
        <label :for="inputId" class="cursor-pointer">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="text-sm font-medium mb-1">{{ uploadText }}</p>
                <p class="text-xs text-gray-400">{{ hintText }}</p>
            </div>
        </label>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
    multiple?: boolean
    accept?: string
    inputId?: string
    uploadText?: string
    hintText?: string
}

const props = withDefaults(defineProps<Props>(), {
    multiple: true,
    accept: 'image/*',
    inputId: 'file-upload',
    uploadText: 'Click to upload images or drag and drop',
    hintText: 'PNG, JPG, GIF up to 10MB'
})

const emit = defineEmits<{
    (e: 'upload', files: File[]): void
}>()

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const handleDrop = (event: DragEvent) => {
    isDragging.value = false
    const files = event.dataTransfer?.files
    if (files && files.length > 0) {
        const fileArray = Array.from(files).filter(file => 
            props.accept === 'image/*' ? file.type.startsWith('image/') : true
        )
        emit('upload', fileArray)
    }
}

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement
    const files = target.files
    if (files && files.length > 0) {
        emit('upload', Array.from(files))
        // Clear the input
        target.value = ''
    }
}
</script>

