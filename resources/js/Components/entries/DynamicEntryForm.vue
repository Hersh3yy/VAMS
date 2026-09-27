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
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-700 dark:text-white"
                required
            />
            <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
        </div>
        
        <!-- Dynamic fields from field_config -->
        <div v-for="field in entryType.field_config" :key="field.name" class="space-y-2">
            <!-- Simple Text Fields -->
            <FormField
                v-if="field.type === 'text'"
                :id="field.name"
                v-model="form.content[field.name]"
                :label="field.label"
                :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
                :required="field.required"
                :error="fieldError(field.name)"
            />

            <!-- Textarea Fields -->
            <FormField
                v-else-if="field.type === 'textarea'"
                :id="field.name"
                v-model="form.content[field.name]"
                type="textarea"
                :label="field.label"
                :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
                :required="field.required"
                :rows="4"
                :error="fieldError(field.name)"
            />

            <!-- Checkbox Fields -->
            <div v-else-if="field.type === 'checkbox'" class="space-y-1">
                <Checkbox
                    :id="field.name"
                    v-model="form.content[field.name]"
                    :label="field.label + (field.required ? ' *' : '')"
                />
                <BaseErrorMessage :error="fieldError(field.name)" />
            </div>

            <!-- Number Fields (stored as numbers, not strings) -->
            <FormField
                v-else-if="field.type === 'number'"
                :id="field.name"
                type="number"
                :model-value="form.content[field.name] === null || form.content[field.name] === undefined ? '' : String(form.content[field.name])"
                :label="field.label"
                :placeholder="field.placeholder"
                :required="field.required"
                :error="fieldError(field.name)"
                @update:model-value="setNumber(field.name, $event)"
            />

            <!-- URL Fields -->
            <FormField
                v-else-if="field.type === 'url'"
                :id="field.name"
                v-model="form.content[field.name]"
                type="url"
                :label="field.label"
                :placeholder="field.placeholder || 'https://'"
                :required="field.required"
                :error="fieldError(field.name)"
            />

            <!-- Date & time Fields (ISO 8601 string with offset, round-tripped) -->
            <FormField
                v-else-if="field.type === 'datetime'"
                :id="field.name"
                type="datetime-local"
                :model-value="toDateTimeLocalValue(form.content[field.name])"
                :label="field.label"
                :required="field.required"
                :error="fieldError(field.name)"
                :hint="datetimeHint(field.name)"
                @update:model-value="setDateTime(field.name, $event)"
            />

            <!-- Select Fields -->
            <div v-else-if="field.type === 'select'" class="mb-4">
                <BaseLabel :text="field.label" :for-id="field.name" :required="field.required" />
                <select
                    :id="field.name"
                    v-model="form.content[field.name]"
                    :required="field.required"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-secondary focus:outline-none focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">{{ field.placeholder || 'Select…' }}</option>
                    <option v-for="option in selectOptions(field)" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <BaseErrorMessage :error="fieldError(field.name)" />
            </div>

            <!-- JSON Fields (validated on blur, stored decoded) -->
            <FormField
                v-else-if="field.type === 'json'"
                :id="field.name"
                type="textarea"
                :rows="6"
                :model-value="jsonDrafts[field.name] ?? ''"
                :label="field.label"
                :placeholder="field.placeholder || '{ } or [ ]'"
                :required="field.required"
                :error="jsonErrors[field.name] || fieldError(field.name)"
                hint="A JSON object or array."
                @update:model-value="jsonDrafts[field.name] = $event"
                @blur="commitJson(field.name)"
            />

            <!-- Entry Relation Fields -->
            <EntryRelationPicker
                v-else-if="field.type === 'entry_relation'"
                :id="field.name"
                v-model="form.content[field.name]"
                class="mb-4"
                :label="field.label"
                :entry-type-slug="field.entry_type_slug"
                :required="field.required"
                :min="field.min"
                :max="field.max"
                :exclude-id="field.exclude_current ? entry?.id : null"
                :error="fieldError(field.name)"
            />

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
                        <BaseButton
                            variant="ghost"
                            size="sm"
                            @click="removeRepeatableItem(field.name, itemIndex)"
                            class="!text-red-500 hover:!text-red-700 !text-sm"
                        >
                            Remove
                        </BaseButton>
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
                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-600 dark:text-white"
                            />
                            
                            <!-- Nested Textarea Field -->
                            <textarea
                                v-else-if="nestedField.type === 'textarea'"
                                :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                v-model="item[nestedField.name]"
                                :placeholder="nestedField.placeholder || `Enter ${nestedField.label.toLowerCase()}...`"
                                :required="nestedField.required"
                                rows="3"
                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-600 dark:text-white"
                            ></textarea>
                            
                            <!-- Nested Image Field -->
                            <div v-else-if="nestedField.type === 'image'" class="space-y-2">
                                <input
                                    :id="`${field.name}_${itemIndex}_${nestedField.name}`"
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageUpload($event, field.name, itemIndex, nestedField.name)"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-600 dark:text-white"
                                />
                                <div v-if="item[nestedField.name]" class="text-xs text-green-600">
                                    Image uploaded: {{ item[nestedField.name] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <BaseButton
                    variant="ghost"
                    size="sm"
                    @click="addRepeatableItem(field)"
                    class="!text-secondary hover:!text-indigo-800 !text-sm"
                >
                    + Add {{ field.label }}
                </BaseButton>
            </div>

            <!-- Image Collection -->
            <ImageCollectionManager
                v-else-if="field.type === 'image_collection'"
                v-model="form.content[field.name]"
                :label="field.label"
                :required="field.required"
                :max-images="field.max"
            />

            <!-- Object/Group Fields -->
            <div v-else-if="field.type === 'object'" class="space-y-3 border rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ field.label }}
                    </label>
                    <BaseButton
                        variant="ghost"
                        size="sm"
                        @click="toggleObjectCollapse(field.name)"
                        class="!text-sm !text-gray-500 hover:!text-gray-700"
                    >
                        {{ collapsedObjects[field.name] ? 'Expand' : 'Collapse' }}
                    </BaseButton>
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
                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-600 dark:text-white"
                        />
                        
                        <textarea
                            v-else-if="nestedField.type === 'textarea'"
                            :id="`${field.name}_${nestedField.name}`"
                            v-model="form.content[field.name][nestedField.name]"
                            :placeholder="nestedField.placeholder || `Enter ${nestedField.label || nestedField.name}...`"
                            rows="2"
                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-600 dark:text-white"
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
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary dark:bg-gray-700 dark:text-white"
            >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>
        
        <div class="flex justify-between">
            <BaseButton
                v-if="showDelete"
                variant="danger"
                @click="$emit('delete')"
            >
                {{ deleteText || 'Delete' }}
            </BaseButton>
            <div v-else></div>
            
            <div class="flex space-x-3">
                <BaseButton
                    variant="secondary"
                    @click="$emit('cancel')"
                >
                    Cancel
                </BaseButton>
                <BaseButton
                    type="submit"
                    variant="primary"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    {{ form.processing ? (submitText || 'Saving...') : (submitText || 'Create') }}
                </BaseButton>
            </div>
        </div>
    </form>
</template>

<script setup lang="ts">
import { useForm, router as inertiaRouter } from '@inertiajs/vue3'
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from 'vue'
import Sortable from 'sortablejs'
import axios from 'axios'
import BaseButton from '@/Components/Base/Button.vue'
import BaseErrorMessage from '@/Components/Base/ErrorMessage.vue'
import BaseLabel from '@/Components/Base/Label.vue'
import Checkbox from '@/Components/atoms/Checkbox.vue'
import EntryRelationPicker from '@/Components/molecules/EntryRelationPicker.vue'
import FormField from '@/Components/molecules/FormField.vue'
import ImageCollectionManager from '@/Components/molecules/ImageCollectionManager.vue'
import { fromDateTimeLocalValue, parseIsoDateTime, toDateTimeLocalValue } from '@/utils/entryFieldFormat'
import { legacyTextFor, normalizeEntryContent } from '@/utils/entryTypes'

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
// Merges existing entry content with field_config to ensure all fields are present
const initializeContent = () => {
    const content: any = {}
    
    const existingContent: any = normalizeEntryContent(props.entry?.content)

    // First, initialize all fields from field_config with default values
    props.entryType.field_config.forEach((field: any) => {
        if (field.type === 'repeatable' || field.type === 'image_collection') {
            // Use existing array if it exists and is valid, otherwise initialize empty array
            content[field.name] = Array.isArray(existingContent[field.name]) 
                ? existingContent[field.name] 
                : []
        } else if (field.type === 'object') {
            // Merge existing object with field defaults
            const existingObject = existingContent[field.name] || {}
            const defaultObject: any = {}
            
            // Initialize nested fields from field_config
            field.fields?.forEach((nestedField: any) => {
                defaultObject[nestedField.name] = existingObject[nestedField.name] ?? ''
            })
            
            content[field.name] = { ...defaultObject, ...existingObject }
        } else if (field.type === 'checkbox') {
            content[field.name] = existingContent[field.name] ?? false
        } else if (field.type === 'entry_relation') {
            content[field.name] = Array.isArray(existingContent[field.name]) ? existingContent[field.name] : []
        } else if (field.type === 'json' || field.type === 'number') {
            content[field.name] = existingContent[field.name] ?? null
        } else {
            // Use existing value if present, otherwise default to empty string
            content[field.name] = existingContent[field.name] ?? ''
        }
    })
    
    // Entries saved by the legacy single-textarea form keep their text under
    // `statement`; prefill it into the first empty text field so it isn't lost.
    const legacyText = legacyTextFor(props.entryType, existingContent)
    if (legacyText) {
        const target =
            props.entryType.field_config.find((field: any) => field.type === 'textarea' && content[field.name] === '') ??
            props.entryType.field_config.find((field: any) => field.type === 'text' && content[field.name] === '')
        if (target) {
            content[target.name] = legacyText
        }
    }

    // Preserve any additional fields that might exist in entry but not in field_config
    // (for backward compatibility)
    Object.keys(existingContent).forEach(key => {
        if (!content.hasOwnProperty(key)) {
            content[key] = existingContent[key]
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
const uploading = ref(false)
const lastEntryId = ref<string | null>(props.entry?.id || null)
const isInitialized = ref(false)

// Initialize form on mount
if (props.entry) {
    form.title = props.entry.title || ''
    form.status = props.entry.status || 'published'
    form.content = initializeContent()
    isInitialized.value = true
}

// Watch for entry changes and re-initialize form content
// Only re-initialize if entry ID changed (different entry)
watch(() => props.entry, (newEntry, oldEntry) => {
    if (!newEntry) {
        return
    }

    // Only re-initialize if entry ID changed (different entry)
    // This prevents re-initialization after save when the same entry is being edited
    if (newEntry.id !== lastEntryId.value) {
        form.title = newEntry.title || ''
        form.status = newEntry.status || 'published'
        form.content = initializeContent()
        syncFieldDrafts()
        lastEntryId.value = newEntry.id
        isInitialized.value = true
    } else if (!isInitialized.value) {
        // If same entry but not initialized yet (e.g., switching from view to edit)
        form.title = newEntry.title || ''
        form.status = newEntry.status || 'published'
        form.content = initializeContent()
        syncFieldDrafts()
        isInitialized.value = true
    }
    // Otherwise, keep the current form state (user's edits are preserved)
})

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

const toggleObjectCollapse = (fieldName: string) => {
    collapsedObjects[fieldName] = !collapsedObjects[fieldName]
}

// Server errors come keyed by field name (validateContent runs on the content
// array), possibly per item ("lineup.0"); older code expected "content.<name>".
const fieldError = (name: string): string | undefined => {
    const errors = form.errors as Record<string, string | undefined>
    if (errors[name] || errors[`content.${name}`]) {
        return errors[name] || errors[`content.${name}`]
    }
    const nestedKey = Object.keys(errors).find(key => key.startsWith(`${name}.`) || key.startsWith(`content.${name}.`))
    return nestedKey ? errors[nestedKey] : undefined
}

const setNumber = (name: string, value: string) => {
    const number = Number(value)
    form.content[name] = value === '' ? null : Number.isFinite(number) ? number : value
}

// datetime: remember each field's original value so its offset survives clearing and re-entering.
const originalDatetimes: Record<string, unknown> = {}

const setDateTime = (name: string, localValue: string) => {
    form.content[name] = fromDateTimeLocalValue(localValue, form.content[name] || originalDatetimes[name])
}

const datetimeHint = (name: string): string => {
    const offset = parseIsoDateTime(form.content[name] || originalDatetimes[name])?.offset
    if (!offset) {
        return 'Saved with your browser\'s time zone offset.'
    }
    return `Time zone offset: ${offset === 'Z' ? 'UTC' : `UTC${offset}`}`
}

const selectOptions = (field: any): Array<{ value: string; label: string }> =>
    (field.options ?? []).map((option: any) =>
        option !== null && typeof option === 'object'
            ? { value: String(option.value ?? option.label), label: String(option.label ?? option.value) }
            : { value: String(option), label: String(option) },
    )

// json: edit a text draft; parse into form.content on blur.
const jsonDrafts = reactive<Record<string, string>>({})
const jsonErrors = reactive<Record<string, string>>({})

const jsonFieldNames = (): string[] =>
    props.entryType.field_config.filter((field: any) => field.type === 'json').map((field: any) => field.name)

const syncFieldDrafts = () => {
    props.entryType.field_config.forEach((field: any) => {
        if (field.type === 'datetime') {
            originalDatetimes[field.name] = form.content[field.name]
        }
    })
    jsonFieldNames().forEach(name => {
        const value = form.content[name]
        jsonDrafts[name] =
            value === null || value === undefined || value === ''
                ? ''
                : typeof value === 'string'
                  ? value
                  : JSON.stringify(value, null, 2)
        jsonErrors[name] = ''
    })
}

const commitJson = (name: string): boolean => {
    const text = (jsonDrafts[name] ?? '').trim()
    if (text === '') {
        form.content[name] = null
        jsonErrors[name] = ''
        return true
    }

    try {
        const parsed = JSON.parse(text)
        if (parsed === null || typeof parsed !== 'object') {
            jsonErrors[name] = 'Enter a JSON object or array.'
            return false
        }
        form.content[name] = parsed
        jsonErrors[name] = ''
        return true
    } catch (error) {
        jsonErrors[name] = `Invalid JSON: ${(error as Error).message}`
        return false
    }
}

syncFieldDrafts()

const handleSubmit = () => {
    const jsonValid = jsonFieldNames()
        .map(name => commitJson(name))
        .every(Boolean)
    if (!jsonValid) {
        return
    }

    // Update lastEntryId to prevent re-initialization after save
    if (props.entry?.id) {
        lastEntryId.value = props.entry.id
    }
    emit('submit', form)
}
</script>
