<template>
    <Head :title="`Create ${entryType.name}`" />

    <AuthenticatedLayout>
        <div class="content-wrapper">
            <div class="content-container">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h1 class="text-2xl font-semibold">Create New {{ entryType.name }}</h1>
                            <Link
                                :href="route('entries.index', { type: entryType.slug })"
                                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                ← Back to {{ entryType.name }}
                            </Link>
                        </div>

                        <SegmentedControl
                            v-model="mode"
                            class="mb-6"
                            aria-label="Create mode"
                            :options="modeOptions"
                        />

                        <div v-if="mode === 'json'" role="tabpanel" aria-label="Insert JSON">
                            <EntryJsonInsert
                                :entry-type="entryType"
                                :initial-payload="jsonImportDraft?.payload"
                                @cancel="mode = 'form'"
                            />
                        </div>

                        <div v-else role="tabpanel" aria-label="Form">
                            <DynamicEntryForm
                                v-if="isFieldFormType"
                                :entry-type="entryType"
                                submit-text="Create"
                                @cancel="router.visit(route('entries.index', { type: entryType.slug }))"
                                @submit="handleDynamicSubmit"
                            />

                            <form v-else class="space-y-6" @submit.prevent="submitForm">
                                <div>
                                    <label for="title" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Title
                                    </label>
                                    <input
                                        id="title"
                                        v-model="form.title"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-secondary focus:outline-none focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        :placeholder="`Give your ${entryType.name} a title...`"
                                        required
                                    />
                                    <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.title }}
                                    </div>
                                </div>

                                <div>
                                    <label for="content" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Content
                                    </label>
                                    <textarea
                                        id="content"
                                        v-model="form.content"
                                        rows="6"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-secondary focus:outline-none focus:ring-secondary dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        :placeholder="`Enter ${entryType.name.toLowerCase()} content...`"
                                        required
                                    ></textarea>
                                    <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.content }}
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3">
                                    <Link
                                        :href="route('entries.index', { type: entryType.slug })"
                                        class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="rounded-md bg-primary px-4 py-2 text-white hover:bg-primary/90 disabled:opacity-50"
                                    >
                                        <span v-if="form.processing">Creating...</span>
                                        <span v-else>Create {{ entryType.name }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DynamicEntryForm from '@/Components/entries/DynamicEntryForm.vue'
import EntryJsonInsert from '@/Components/entries/EntryJsonInsert.vue'
import SegmentedControl from '@/Components/molecules/SegmentedControl.vue'
import { usesFieldForm } from '@/utils/entryTypes'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    entryType: Object,
    jsonImportDraft: {
        type: Object,
        default: null,
    },
});

const mode = ref(props.jsonImportDraft?.mode === 'json' ? 'json' : 'form')

const modeOptions = [
    { label: 'Form', value: 'form' },
    { label: 'Insert JSON', value: 'json' },
]

const isFieldFormType = computed(() => usesFieldForm(props.entryType));

const form = useForm({
    title: '',
    content: '',
    status: 'published',
    entry_type_id: ''
})

const submitForm = () => {
    form.entry_type_id = props.entryType.id;
    form.post(route('entries.store'));
}

const handleDynamicSubmit = (dynamicForm) => {
    dynamicForm.post(route('entries.store'), {
        preserveScroll: true,
    });
};
</script>
