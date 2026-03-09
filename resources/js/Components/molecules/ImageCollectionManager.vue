<template>
    <div class="space-y-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <ImageUploadZone
            :upload-text="uploadText"
            :hint-text="hintText"
            @upload="handleUpload"
        />
        
        <div 
            v-if="modelValue && modelValue.length > 0" 
            :ref="setGridRef"
            class="grid grid-cols-3 gap-4"
        >
            <div 
                v-for="(image, imageIndex) in modelValue" 
                :key="imageIndex" 
                :data-index="imageIndex"
                class="relative group border rounded-lg overflow-hidden cursor-move hover:shadow-lg transition-shadow"
            >
                <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                    <img 
                        v-if="image.url || image.path" 
                        :src="image.url || getImagePublicUrl(image.path)" 
                        :alt="image.alt || 'Image'" 
                        class="w-full h-full object-cover" 
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                        type="button"
                        @click="removeImage(imageIndex)"
                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-lg"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="absolute top-2 left-2 bg-gray-800 bg-opacity-75 text-white px-2 py-1 rounded text-xs">
                    {{ imageIndex + 1 }}
                </div>
                <div class="p-2 bg-white dark:bg-gray-800">
                    <input
                        v-model="image.alt"
                        type="text"
                        placeholder="Alt text..."
                        class="w-full text-xs px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        @input="emitUpdate"
                    />
                </div>
            </div>
        </div>
        
        <p v-if="maxImages && modelValue.length >= maxImages" class="text-sm text-amber-600">
            Maximum of {{ maxImages }} images reached
        </p>
        
    </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted } from 'vue'
import Sortable from 'sortablejs'
import ImageUploadZone from '@/Components/atoms/ImageUploadZone.vue'
import { useCsrfToken } from '@/composables/shared/useCsrfToken'

interface Image {
    path: string
    url?: string
    alt: string
    caption?: string
}

interface Props {
    modelValue: Image[]
    label: string
    required?: boolean
    maxImages?: number
    uploadText?: string
    hintText?: string
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    uploadText: 'Click to upload images or drag and drop',
    hintText: 'PNG, JPG, GIF up to 10MB'
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: Image[]): void
}>()

const { getCsrfToken } = useCsrfToken()
const gridRef = ref<HTMLElement | null>(null)
let sortableInstance: any = null

const setGridRef = (el: any) => {
    gridRef.value = el
    initializeSortable()
}

const initializeSortable = () => {
    // Destroy existing instance if any
    if (sortableInstance) {
        sortableInstance.destroy()
        sortableInstance = null
    }
    
    nextTick(() => {
        if (gridRef.value && props.modelValue.length > 0) {
            sortableInstance = Sortable.create(gridRef.value, {
                animation: 150,
                ghostClass: 'opacity-50',
                handle: '.cursor-move',
                onEnd: (evt: any) => {
                    const oldIndex = evt.oldIndex
                    const newIndex = evt.newIndex
                    
                    if (oldIndex !== newIndex && oldIndex !== undefined && newIndex !== undefined) {
                        const images = [...props.modelValue]
                        const movedImage = images.splice(oldIndex, 1)[0]
                        images.splice(newIndex, 0, movedImage)
                        emit('update:modelValue', images)
                    }
                }
            })
        }
    })
}

// Only re-initialize when the number of images changes, not on every edit
watch(() => props.modelValue.length, () => {
    initializeSortable()
})

onUnmounted(() => {
    if (sortableInstance) {
        sortableInstance.destroy()
    }
})

const handleUpload = async (files: File[]) => {
    const uploadedImages: Image[] = []
    const MAX_FILE_SIZE = 1.99 * 1024 * 1024; // 1.99MB in bytes
    
    try {
        // Process images: convert to WebP and compress to under 1.99MB
        const { processImagesForUpload } = await import('@/utils/imageConverter')
        const processedFiles = await processImagesForUpload(
            files,
            MAX_FILE_SIZE,
            (processed, total) => {
                console.log(`Processing images: ${processed}/${total}`)
            }
        )

        if (processedFiles.length === 0) {
            alert('No images could be processed. Please check your files and try again.')
            return
        }

        // Log conversion stats
        const originalTotal = files.reduce((sum, f) => sum + f.size, 0)
        const processedTotal = processedFiles.reduce((sum, f) => sum + f.size, 0)
        const savings = ((1 - processedTotal / originalTotal) * 100).toFixed(1)
        console.log(
            `Image processing complete: ${files.length} files, ${(originalTotal / 1024 / 1024).toFixed(2)}MB → ${(processedTotal / 1024 / 1024).toFixed(2)}MB (${savings}% reduction)`
        )
        
        for (const file of processedFiles) {
            const formData = new FormData()
            formData.append('file', file)
            
            try {
                const token = getCsrfToken()
                const response = await fetch(route('media.upload'), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                })
                
                if (!response.ok) {
                    throw new Error(`Upload failed with status ${response.status}`)
                }
                
                const data = await response.json()
                
                if (data.success) {
                    uploadedImages.push({
                        path: data.data.path,
                        url: data.data.url,
                        alt: '',
                        caption: ''
                    })
                }
            } catch (error) {
                console.error('Image upload failed:', error)
                alert(`Failed to upload ${file.name}. Please try again.`)
            }
        }
    } catch (error) {
        console.error('Error processing images:', error)
        alert('Failed to process images. Please try again.')
        return
    }
    
    if (uploadedImages.length > 0) {
        emit('update:modelValue', [...props.modelValue, ...uploadedImages])
    }
}

const removeImage = (imageIndex: number) => {
    const images = [...props.modelValue]
    images.splice(imageIndex, 1)
    emit('update:modelValue', images)
}

const emitUpdate = () => {
    emit('update:modelValue', props.modelValue)
}

const getImagePublicUrl = (path: string) => {
    if (!path) return ''
    // If it's already a full URL (from DigitalOcean Spaces), return it
    if (path.startsWith('http')) return path
    // If it's a path but no URL, it should have come from backend with full URL
    // This fallback should rarely be needed, but helps with local dev/testing
    return path
}
</script>

