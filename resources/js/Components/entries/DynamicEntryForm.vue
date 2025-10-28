<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Title is always present -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Title <span class="text-red-500">*</span>
            </label>
            <input
                id="title"
                v-model="form.title"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                required
            />
            <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
        </div>
        
        <!-- Dynamic fields from field_config -->
        <div v-for="field in entryType.field_config" :key="field.name" class="space-y-2">
            <!-- Simple Text Fields -->
            <div v-if="field.type === 'text'">
                <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                <input
                    :id="field.name"
                    v-model="form.content[field.name]"
                    type="text"
                    :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
                    :required="field.required"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
            </div>

            <!-- Textarea Fields -->
            <div v-else-if="field.type === 'textarea'">
                <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                <textarea
                    :id="field.name"
                    v-model="form.content[field.name]"
                    :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
                    :required="field.required"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                ></textarea>
            </div>

            <!-- Repeatable Sections -->
            <div v-else-if="field.type === 'repeatable'" class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                
                <div v-for="(item, itemIndex) in form.content[field.name]" :key="itemIndex" 
                     class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ field.label }} {{ itemIndex + 1 }}
                        </span>
                        <button
                            type="button"
                            @click="removeRepeatableItem(field.name, itemIndex)"
                            class="text-red-500 hover:text-red-700 text-sm"
                        >
                            Remove
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <div v-for="nestedField in field.fields" :key="nestedField.name" class="space-y-1">
                            <label :for="`${field.name}_${itemIndex}_${nestedField.name}`" 
                                   class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ nestedField.label }}
                                <span v-if="nestedField.required" class="text-red-500">*</span>
                            </label>
                            
                            <!-- Nested Text Field -->
                            <input
                                v-if="nestedField.type === 'text'"
                                :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                v-model="item[nestedField.name]"
                                type="text"
                                :placeholder="nestedField.placeholder || `Enter ${nestedField.label.toLowerCase()}...`"
                                :required="nestedField.required"
                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                            />
                            
                            <!-- Nested Textarea Field -->
                            <textarea
                                v-else-if="nestedField.type === 'textarea'"
                                :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                v-model="item[nestedField.name]"
                                :placeholder="nestedField.placeholder || `Enter ${nestedField.label.toLowerCase()}...`"
                                :required="nestedField.required"
                                rows="3"
                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                            ></textarea>
                            
                            <!-- Nested Image Field -->
                            <div v-else-if="nestedField.type === 'image'" class="space-y-2">
                                <input
                                    :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageUpload($event, field.name, itemIndex, nestedField.name)"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                                />
                                <div v-if="item[nestedField.name]" class="text-xs text-green-600">
                                    Image uploaded: {{ item[nestedField.name] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button
                    type="button"
                    @click="addRepeatableItem(field)"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    + Add {{ field.label }}
                </button>
            </div>

            <!-- Image Collection -->
            <div v-else-if="field.type === 'image_collection'" class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>
                
                <div 
                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center transition-colors"
                    :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900': isDragging[field.name] }"
                    @dragover.prevent="isDragging[field.name] = true"
                    @dragleave.prevent="isDragging[field.name] = false"
                    @drop.prevent="handleImageDrop($event, field.name)"
                >
                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        @change="handleImageCollectionUpload($event, field.name)"
                        class="hidden"
                        :id="`${field.name}_upload`"
                        :ref="`${field.name}_input`"
                    />
                    <label :for="`${field.name}_upload`" class="cursor-pointer">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="text-sm font-medium mb-1">Click to upload images or drag and drop</p>
                            <p class="text-xs text-gray-400">PNG, JPG, GIF up to 10MB</p>
                        </div>
                    </label>
                </div>
                
                <div 
                    v-if="form.content[field.name] && form.content[field.name].length > 0" 
                    :ref="`imageGrid_${field.name}`"
                    class="grid grid-cols-3 gap-4"
                >
                    <div 
                        v-for="(image, imageIndex) in form.content[field.name]" 
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
                                @click="removeImage(field.name, imageIndex)"
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
                            />
                        </div>
                    </div>
                </div>
                
                <p v-if="field.max && form.content[field.name]?.length >= field.max" class="text-sm text-amber-600">
                    Maximum of {{ field.max }} images reached
                </p>
            </div>

            <!-- Object/Group Fields -->
            <div v-else-if="field.type === 'object'" class="space-y-3 border rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ field.label }}
                    </label>
                    <button
                        type="button"
                        @click="toggleObjectCollapse(field.name)"
                        class="text-sm text-gray-500 hover:text-gray-700"
                    >
                        {{ collapsedObjects[field.name] ? 'Expand' : 'Collapse' }}
                    </button>
                </div>
                
                <div v-if="!collapsedObjects[field.name]" class="space-y-3">
                    <div v-for="nestedField in field.fields" :key="nestedField.name" class="space-y-1">
                        <label :for="`${field.name}_${nestedField.name}`" 
                               class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ nestedField.label || nestedField.name }}
                        </label>
                        
                        <input
                            v-if="nestedField.type === 'text'"
                            :id="`${field.name}_${nestedField.name}`"
                            v-model="form.content[field.name][nestedField.name]"
                            type="text"
                            :placeholder="nestedField.placeholder || `Enter ${nestedField.label || nestedField.name}...`"
                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                        />
                        
                        <textarea
                            v-else-if="nestedField.type === 'textarea'"
                            :id="`${field.name}_${nestedField.name}`"
                            v-model="form.content[field.name][nestedField.name]"
                            :placeholder="nestedField.placeholder || `Enter ${nestedField.label || nestedField.name}...`"
                            rows="2"
                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status (optional) -->
        <div v-if="showStatus">
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Status
            </label>
            <select
                id="status"
                v-model="form.status"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>
        
        <div class="flex justify-between">
            <button
                v-if="showDelete"
                type="button"
                @click="$emit('delete')"
                class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
            >
                {{ deleteText || 'Delete' }}
            </button>
            <div v-else></div>
            
            <div class="flex space-x-3">
                <button
                    type="button"
                    @click="$emit('cancel')"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
                >
                    <span v-if="form.processing">{{ submitText || 'Saving...' }}</span>
                    <span v-else>{{ submitText || 'Create' }}</span>
                </button>
            </div>
        </div>
    </form>
</template>

<script setup lang="ts">
import { useForm, router as inertiaRouter } from '@inertiajs/vue3'
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from 'vue'
import Sortable from 'sortablejs'
import axios from 'axios'

interface Props {
    entryType: any
    entry?: any
    showStatus?: boolean
    submitText?: string
    showDelete?: boolean
    deleteText?: string
}

const props = defineProps<Props>()
const emit = defineEmits(['cancel', 'submit', 'delete'])

// Initialize form content structure from field_config
const initializeContent = () => {
    const content: any = {}
    
    if (props.entry?.content) {
        return props.entry.content
    }
    
    props.entryType.field_config.forEach((field: any) => {
        if (field.type === 'repeatable' || field.type === 'image_collection') {
            content[field.name] = []
        } else if (field.type === 'object') {
            content[field.name] = {}
        } else if (field.type === 'checkbox') {
            content[field.name] = false
        } else {
            content[field.name] = ''
        }
    })
    
    return content
}

const form = useForm({
    title: props.entry?.title || '',
    content: initializeContent(),
    status: props.entry?.status || 'published',
    entry_type_id: props.entryType.id
})

const collapsedObjects = reactive<Record<string, boolean>>({})
const isDragging = reactive<Record<string, boolean>>({})
const sortableInstances = ref<Record<string, any>>({})
const uploading = ref(false)

const addRepeatableItem = (field: any) => {
    const newItem: any = {}
    field.fields?.forEach((f: any) => {
        newItem[f.name] = ''
    })
    form.content[field.name].push(newItem)
}

const removeRepeatableItem = (fieldName: string, itemIndex: number) => {
    form.content[fieldName].splice(itemIndex, 1)
}

const handleImageUpload = (event: Event, fieldName: string, itemIndex: number, nestedFieldName: string) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        // For now, just store the file name - in production, upload to server
        form.content[fieldName][itemIndex][nestedFieldName] = file.name
    }
}

const handleImageCollectionUpload = async (event: Event, fieldName: string) => {
    const target = event.target as HTMLInputElement
    const files = target.files
    if (!files || files.length === 0) return
    
    uploading.value = true
    
    try {
        for (const file of Array.from(files)) {
            const formData = new FormData()
            formData.append('file', file)
            
            const response = await axios.post('/api/media/upload', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            
            if (response.data.success) {
                form.content[fieldName].push({
                    path: response.data.data.path,
                    url: response.data.data.url,
                    alt: '',
                    caption: ''
                })
            }
        }
    } catch (error) {
        console.error('Image upload failed:', error)
        alert('Failed to upload images. Please try again.')
    } finally {
        uploading.value = false
        // Clear the input
        target.value = ''
    }
}

const handleImageDrop = async (event: DragEvent, fieldName: string) => {
    isDragging[fieldName] = false
    const files = event.dataTransfer?.files
    if (!files || files.length === 0) return
    
    uploading.value = true
    
    try {
        for (const file of Array.from(files)) {
            if (!file.type.startsWith('image/')) continue
            
            const formData = new FormData()
            formData.append('file', file)
            
            const response = await axios.post('/api/media/upload', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            
            if (response.data.success) {
                form.content[fieldName].push({
                    path: response.data.data.path,
                    url: response.data.data.url,
                    alt: '',
                    caption: ''
                })
            }
        }
    } catch (error) {
        console.error('Image upload failed:', error)
        alert('Failed to upload images. Please try again.')
    } finally {
        uploading.value = false
    }
}

const removeImage = (fieldName: string, imageIndex: number) => {
    form.content[fieldName].splice(imageIndex, 1)
}

const getImagePublicUrl = (path: string) => {
    if (!path) return ''
    // If it's already a full URL, return it
    if (path.startsWith('http')) return path
    // Otherwise, construct the public URL
    return `/storage/${path}`
}

// Initialize Sortable for image grids
const initializeSortable = () => {
    props.entryType.field_config.forEach((field: any) => {
        if (field.type === 'image_collection' && form.content[field.name]?.length > 0) {
            nextTick(() => {
                const gridElement = document.querySelector(`[ref="imageGrid_${field.name}"]`) as HTMLElement
                if (gridElement && !sortableInstances.value[field.name]) {
                    sortableInstances.value[field.name] = Sortable.create(gridElement, {
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: (evt: any) => {
                            const oldIndex = evt.oldIndex
                            const newIndex = evt.newIndex
                            
                            if (oldIndex !== newIndex && oldIndex !== undefined && newIndex !== undefined) {
                                const images = form.content[field.name]
                                const movedImage = images.splice(oldIndex, 1)[0]
                                images.splice(newIndex, 0, movedImage)
                            }
                        }
                    })
                }
            })
        }
    })
}

// Watch for changes in image collections to reinitialize sortable
watch(() => form.content, () => {
    initializeSortable()
}, { deep: true })

onMounted(() => {
    initializeSortable()
})

onUnmounted(() => {
    // Cleanup sortable instances
    Object.values(sortableInstances.value).forEach((instance: any) => {
        if (instance) instance.destroy()
    })
})

const toggleObjectCollapse = (fieldName: string) => {
    collapsedObjects[fieldName] = !collapsedObjects[fieldName]
}

const handleSubmit = () => {
    emit('submit', form)
}
</script>
