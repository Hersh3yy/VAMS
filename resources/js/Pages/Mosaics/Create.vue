<template>
    <Head title="Create Mosaic" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="page-title">Create New Mosaic</h1>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <form @submit.prevent="createMosaic" class="space-y-6 p-6">
                        <FormField
                            v-model="form.title"
                            label="Title"
                            id="title"
                            type="text"
                            required
                            placeholder="Enter mosaic title"
                            :error="form.errors.title"
                        />

                        <div>
                            <label
                                for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Description (Optional)
                            </label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                :rows="3"
                                placeholder="Enter mosaic description"
                                :error="form.errors.description"
                            />
                        </div>

                        <div>
                            <label
                                for="columns"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Number of Columns
                            </label>
                            <select
                                id="columns"
                                v-model="form.columns"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="2">2 Columns</option>
                                <option value="3">3 Columns</option>
                                <option value="4">4 Columns</option>
                                <option value="5">5 Columns</option>
                            </select>
                            <div v-if="form.errors.columns" class="mt-1 text-sm text-red-600">
                                {{ form.errors.columns }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <Link :href="route('mosaics.index')">
                                <Button variant="secondary">Cancel</Button>
                            </Link>
                            <Button
                                type="submit"
                                variant="primary"
                                :disabled="form.processing"
                                :loading="form.processing"
                            >
                                Create Mosaic
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import Button from '@/Components/Base/Button.vue';
import Textarea from '@/Components/Base/Textarea.vue';
import FormField from '@/Components/molecules/FormField.vue';
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
