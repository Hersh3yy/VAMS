<template>
    <Head title="Create Mosaic" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="header-title">
                Create New Mosaic
            </h2>
        </template>

        <div class="content-wrapper">
            <div class="form-container">
                <div class="form-card">
                    <div class="form-body">
                        <form @submit.prevent="submit" class="form-layout">
                            <div class="form-group">
                                <label class="form-label">
                                    Title
                                </label>
                                <input 
                                    type="text"
                                    v-model="form.title"
                                    class="form-input"
                                >
                                <div v-if="form.errors.title" class="form-error">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Description
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="form-textarea"
                                ></textarea>
                                <div v-if="form.errors.description" class="form-error">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div class="form-actions">
                                <Link
                                    :href="route('mosaics.index')"
                                    class="cancel-button"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    class="submit-button"
                                    :disabled="form.processing"
                                >
                                    Create Mosaic
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    title: '',
    description: '',
    theme_settings: JSON.stringify({
        background_color: '#ffffff',
        text_color: '#000000',
    })
});

const submit = () => {
    console.log('Submitting form with data:', form);
    
    form.post(route('mosaics.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect is handled by the controller
            console.log('Form submitted successfully');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        }
    });
};
</script>

<style scoped>
.header-title {
    @apply font-semibold text-xl text-gray-800 leading-tight;
}

.content-wrapper {
    @apply py-12;
}

.form-container {
    @apply max-w-7xl mx-auto sm:px-6 lg:px-8;
}

.form-card {
    @apply bg-white overflow-hidden shadow-sm rounded-lg;
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
    @apply text-red-500 text-sm mt-1;
}

.form-actions {
    @apply flex justify-end gap-4;
}

.cancel-button {
    @apply px-4 py-2 text-gray-700 hover:text-gray-900;
}

.submit-button {
    @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded;
}
</style> 