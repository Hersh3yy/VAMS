<template>
    <div class="space-y-6">
        <form class="space-y-4" @submit.prevent="review">
            <div>
                <BaseLabel text="JSON payload" for-id="entry-json-payload" />
                <p class="mt-1 mb-2 text-sm text-gray-600 dark:text-gray-400">
                    Paste a JSON object or an array of objects for
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ entryType.name }}</span>.
                    You will review what will be added before anything is created.
                </p>
                <div class="mt-1 font-mono text-sm">
                    <BaseTextarea
                        id="entry-json-payload"
                        v-model="jsonText"
                        :rows="16"
                        placeholder='[{"title":"...","content":{...}}]'
                        :error="payloadError"
                    />
                </div>
                <BaseErrorMessage
                    v-for="(message, key) in indexedErrors"
                    :key="key"
                    :error="`${key}: ${message}`"
                />
            </div>

            <div class="flex justify-end gap-3">
                <BaseButton type="button" variant="ghost" @click="$emit('cancel')">
                    Back to form
                </BaseButton>
                <BaseButton type="submit" variant="primary" :loading="form.processing" :disabled="form.processing">
                    Review entries
                </BaseButton>
            </div>
        </form>

        <CollapsiblePanel title="Prompt guide">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Copy this into an LLM with your source data to generate a valid payload.
            </p>
            <div class="flex flex-wrap gap-2">
                <BaseButton type="button" variant="ghost" size="sm" @click="copyPrompt">
                    {{ copiedPrompt ? 'Copied prompt' : 'Copy prompt' }}
                </BaseButton>
                <BaseButton type="button" variant="ghost" size="sm" @click="copyExample">
                    {{ copiedExample ? 'Copied example' : 'Copy example' }}
                </BaseButton>
            </div>
            <pre
                class="max-h-80 overflow-auto whitespace-pre-wrap rounded-md border border-gray-200 bg-white p-3 font-mono text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"
            >{{ guide.prompt }}</pre>
        </CollapsiblePanel>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/Button.vue'
import BaseErrorMessage from '@/Components/Base/ErrorMessage.vue'
import BaseLabel from '@/Components/Base/Label.vue'
import BaseTextarea from '@/Components/Base/Textarea.vue'
import CollapsiblePanel from '@/Components/molecules/CollapsiblePanel.vue'
import { buildEntryJsonGuide } from '@/utils/entryJsonGuide'
import type { EntryType } from '@/types/entryField'

const props = defineProps<{
    entryType: EntryType
    initialPayload?: Record<string, unknown> | Record<string, unknown>[] | null
}>()

defineEmits<{
    cancel: []
}>()

const guide = computed(() => buildEntryJsonGuide(props.entryType))

const initialJsonText = (): string => {
    if (props.initialPayload !== undefined && props.initialPayload !== null) {
        return JSON.stringify(props.initialPayload, null, 2)
    }

    return guide.value.example
}

const jsonText = ref(initialJsonText())
const clientError = ref('')
const copiedPrompt = ref(false)
const copiedExample = ref(false)

const form = useForm({
    entry_type_id: props.entryType.id,
    payload_json: '',
})

const payloadError = computed(() => {
    if (clientError.value) {
        return clientError.value
    }

    const errors = form.errors as Record<string, string | undefined>

    return errors.payload || errors.payload_json || errors.error
})

const indexedErrors = computed(() => {
    const errors: Record<string, string> = {}

    Object.entries(form.errors as Record<string, string | string[]>).forEach(([key, message]) => {
        if (key === 'payload' || key === 'payload_json' || key === 'entry_type_id' || key === 'error') {
            return
        }

        errors[key] = Array.isArray(message) ? message.join(' ') : String(message)
    })

    return errors
})

const copyText = async (text: string, flag: 'prompt' | 'example'): Promise<void> => {
    try {
        await navigator.clipboard.writeText(text)
        if (flag === 'prompt') {
            copiedPrompt.value = true
            setTimeout(() => {
                copiedPrompt.value = false
            }, 2000)
        } else {
            copiedExample.value = true
            setTimeout(() => {
                copiedExample.value = false
            }, 2000)
        }
    } catch {
        clientError.value = 'Could not copy to clipboard.'
    }
}

const copyPrompt = (): void => {
    void copyText(guide.value.prompt, 'prompt')
}

const copyExample = (): void => {
    void copyText(guide.value.example, 'example')
}

const review = (): void => {
    clientError.value = ''

    try {
        const parsed: unknown = JSON.parse(jsonText.value)

        if (parsed === null || typeof parsed !== 'object') {
            clientError.value = 'JSON must be an object or an array of objects.'
            return
        }
    } catch (error) {
        clientError.value = error instanceof Error
            ? `Invalid JSON: ${error.message}`
            : 'Invalid JSON.'
        return
    }

    form.entry_type_id = props.entryType.id
    form.payload_json = jsonText.value
    form.post(route('entries.preview-json'), {
        preserveScroll: true,
    })
}
</script>
