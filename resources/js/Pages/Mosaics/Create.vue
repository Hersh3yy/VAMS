<template>
    <Head title="Create Mosaic" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="page-title">Create New Mosaic</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <form @submit.prevent="createMosaic" class="p-6">
                        <div class="mb-6">
                            <label for="title" class="form-label"> Title </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="form-input"
                                required
                            />
                            <div v-if="form.errors.title" class="form-error">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="description" class="form-label">
                                Description (Optional)
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="form-input"
                            />
                            <div v-if="form.errors.description" class="form-error">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="columns" class="form-label"> Number of Columns </label>
                            <select id="columns" v-model="form.columns" class="form-input" required>
                                <option value="2">2 Columns</option>
                                <option value="3">3 Columns</option>
                                <option value="4">4 Columns</option>
                                <option value="5">5 Columns</option>
                            </select>
                            <div v-if="form.errors.columns" class="form-error">
                                {{ form.errors.columns }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <Link :href="route('mosaics.index')" class="btn-danger"> Cancel </Link>
                            <button type="submit" class="btn-primary" :disabled="form.processing">
                                Create Mosaic
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    description: '',
    columns: 3
});

const createMosaic = () => {
    form.post(route('mosaics.store'), {
        onSuccess: () => {
            // Redirect to edit page will be handled by the controller
        }
    });
};
</script>

<style scoped>
.header-title {
    @apply text-xl font-semibold leading-tight text-gray-800;
}

.content-wrapper {
    @apply py-12;
}

.form-container {
    @apply mx-auto max-w-7xl sm:px-6 lg:px-8;
}

.form-card {
    @apply overflow-hidden rounded-lg bg-white shadow-sm;
}

.form-body {
    @apply p-6;
}

.form-layout {
    @apply space-y-6;
}

.form-group {
    @apply mb-4;
}

.form-label {
    @apply block text-sm font-medium text-gray-700;
}

.form-input {
    @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.form-textarea {
    @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.form-error {
    @apply mt-1 text-sm text-red-500;
}

.form-actions {
    @apply flex justify-end gap-4;
}

.cancel-button {
    @apply px-4 py-2 text-gray-700 hover:text-gray-900;
}

.submit-button {
    @apply rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700;
}
</style>
