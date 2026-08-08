<template>
    <Head title="Manage Entry Types" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold leading-tight text-gray-800">
                    Manage Entry Types
                </h1>
                <div class="flex space-x-3">
                    <button
                        @click="openCreateModal"
                        class="hover:bg-green-600 rounded bg-green-500 px-4 py-2 text-white"
                    >
                        Create Entry Type
                    </button>
                    <Link
                        :href="route('admin.dashboard')"
                        class="hover:bg-gray-600 rounded bg-gray-500 px-4 py-2 text-white"
                    >
                        Back to Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <!-- Flash Messages -->
                <div
                    v-if="$page.props.flash?.success"
                    class="relative mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700"
                    role="alert"
                >
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>
                <div
                    v-if="$page.props.flash?.error"
                    class="relative mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700"
                    role="alert"
                >
                    <span class="block sm:inline">{{ $page.props.flash.error }}</span>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="entryTypes.length === 0" class="text-center py-8">
                            <p class="text-gray-500 mb-4">No entry types created yet.</p>
                            <button
                                @click="openCreateModal"
                                class="inline-block rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600"
                            >
                                Create First Entry Type
                            </button>
                        </div>

                        <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="entryType in entryTypes"
                                :key="entryType.id"
                                class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                            >
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="text-lg font-medium">{{ entryType.name }}</h3>
                                        <p class="text-sm text-gray-500">{{ entryType.slug }}</p>
                                    </div>
                                    <span
                                        class="inline-block rounded px-2 py-1 text-xs"
                                        :class="{
                                            'bg-green-100 text-green-800': entryType.is_active,
                                            'bg-gray-100 text-gray-800': !entryType.is_active
                                        }"
                                    >
                                        {{ entryType.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <p v-if="entryType.description" class="text-sm text-gray-600 mb-3">
                                    {{ entryType.description }}
                                </p>

                                <div class="text-sm text-gray-500 mb-3">
                                    {{ entryType.entries_count }} entries created
                                </div>

                                <div class="text-xs text-gray-500 mb-3">
                                    <div>Fields:</div>
                                    <ul class="list-disc list-inside ml-2">
                                        <li v-for="field in entryType.field_config" :key="field.name">
                                            {{ field.label }} ({{ field.type }})
                                            <span v-if="field.type === 'repeatable' && field.fields" class="text-gray-400">
                                                - {{ field.fields.length }} nested fields
                                            </span>
                                            <span v-if="field.type === 'image_collection'" class="text-gray-400">
                                                - Max: {{ field.max || 'unlimited' }}
                                            </span>
                                            <span v-if="field.required" class="text-red-500">*</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="flex space-x-2">
                                    <button
                                        @click="openEditModal(entryType)"
                                        class="text-blue-500 hover:text-blue-700 text-sm"
                                    >
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entry Type Modal -->
        <EntryTypeModal
            :show="showModal"
            :entry-type="selectedEntryType"
            @close="closeModal"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EntryTypeModal from '@/Components/admin/EntryTypeModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    entryTypes: {
        type: Array,
        default: () => []
    }
});

const showModal = ref(false);
const selectedEntryType = ref(null);

const openCreateModal = () => {
    selectedEntryType.value = null;
    showModal.value = true;
};

const openEditModal = (entryType) => {
    selectedEntryType.value = entryType;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedEntryType.value = null;
};
</script>
