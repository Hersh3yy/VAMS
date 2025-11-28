<template>
    <div class="space-y-4 max-h-[70vh] overflow-y-auto">
        <FieldDisplay
            v-for="field in displayFields"
            :key="field.name"
            :label="field.label"
            :value="getFieldValue(field.name)"
            :type="field.type"
        />
        
        <div class="text-sm text-gray-500 dark:text-gray-400 pt-4 border-t">
            Created {{ formatDate(entry.created_at) }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import FieldDisplay from '@/Components/molecules/FieldDisplay.vue'

interface Props {
    entry: any
    entryType: any
}

const props = defineProps<Props>()

const displayFields = computed(() => {
    return props.entryType?.field_config || []
})

const getFieldValue = (fieldName: string) => {
    // Normalize content - handle both object and string cases
    let content: any = {}
    if (props.entry?.content) {
        if (typeof props.entry.content === 'string') {
            try {
                content = JSON.parse(props.entry.content)
            } catch {
                // If it's not valid JSON, treat as simple string content
                content = { statement: props.entry.content }
            }
        } else if (typeof props.entry.content === 'object') {
            content = props.entry.content
        }
    }
    
    // Return the field value, or null if it doesn't exist
    return content[fieldName] ?? null
}

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString()
}
</script>
