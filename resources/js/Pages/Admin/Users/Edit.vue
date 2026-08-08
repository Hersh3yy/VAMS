<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit User: {{ user.name }}
                </h1>
                <div>
                    <Link
                        :href="route('admin.users.index')"
                        class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600"
                    >
                        Back to Users
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
                        <form @submit.prevent="form.patch(route('admin.users.update', user.id))">
                            <div class="mb-4">
                                <label for="name" class="form-label">Name</label>
                                <input
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    class="form-input"
                                />
                                <div v-if="form.errors.name" class="form-error">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    class="form-input"
                                />
                                <div v-if="form.errors.email" class="form-error">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    class="form-input"
                                />
                                <div class="mt-1 text-sm text-gray-500">
                                    Leave blank to keep current password
                                </div>
                                <div v-if="form.errors.password" class="form-error">
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <div class="mb-4 flex space-x-4">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_admin"
                                        v-model="form.is_admin"
                                        class="form-checkbox"
                                    />
                                    <label for="is_admin" class="form-label ml-2">Admin User</label>
                                    <div v-if="form.errors.is_admin" class="form-error">
                                        {{ form.errors.is_admin }}
                                    </div>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_approved"
                                        v-model="form.is_approved"
                                        class="form-checkbox"
                                    />
                                    <label for="is_approved" class="form-label ml-2"
                                        >Approved</label
                                    >
                                    <div v-if="form.errors.is_approved" class="form-error">
                                        {{ form.errors.is_approved }}
                                    </div>
                                </div>
                            </div>

                            <!-- Entry Type Permissions -->
                            <div class="mb-6 rounded-lg bg-gray-50 p-4">
                                <h3 class="mb-3 text-lg font-medium text-gray-900">
                                    Entry Type Permissions
                                </h3>
                                <p class="mb-4 text-sm text-gray-600">
                                    Control which entry types this user can access. Users can only access checked entry types.
                                </p>
                                <div class="space-y-3">
                                    <div v-for="entryType in entryTypes" :key="entryType.id" class="flex items-center">
                                        <input
                                            :id="`entry_type_${entryType.slug}`"
                                            type="checkbox"
                                            :value="entryType.slug"
                                            v-model="form.entry_type_permissions"
                                            class="form-checkbox"
                                        />
                                        <label :for="`entry_type_${entryType.slug}`" class="form-label ml-2">
                                            {{ entryType.name }}
                                            <span class="text-xs text-gray-500">({{ entryType.slug }})</span>
                                        </label>
                                    </div>
                                </div>
                                <div v-if="form.errors.entry_type_permissions" class="form-error">
                                    {{ form.errors.entry_type_permissions }}
                                </div>
                            </div>

                            <!-- API Key Management -->
                            <div class="mb-6 rounded-lg bg-gray-50 p-4">
                                <h3 class="mb-3 text-lg font-medium text-gray-900">
                                    API Key Management
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="form-label">Current API Key</label>
                                        <div class="flex items-center space-x-2">
                                            <input
                                                type="text"
                                                :value="
                                                    showApiKey
                                                        ? user.api_key
                                                        : '••••••••••••••••••••••••••••••••'
                                                "
                                                readonly
                                                class="form-input flex-1 font-mono text-sm"
                                            />
                                            <button
                                                type="button"
                                                @click="showApiKey = !showApiKey"
                                                class="rounded bg-gray-200 px-3 py-2 text-sm hover:bg-gray-300"
                                            >
                                                {{ showApiKey ? 'Hide' : 'Show' }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="copyApiKey"
                                                class="rounded bg-secondary px-3 py-2 text-sm text-primary hover:brightness-90"
                                            >
                                                Copy
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button
                                            type="button"
                                            @click="regenerateApiKey"
                                            class="rounded bg-yellow-500 px-4 py-2 text-white hover:bg-yellow-600"
                                            :disabled="regenerating"
                                        >
                                            {{
                                                regenerating
                                                    ? 'Regenerating...'
                                                    : 'Regenerate API Key'
                                            }}
                                        </button>
                                        <p class="self-center text-sm text-gray-600">
                                            This will invalidate the current key. Make sure to
                                            update any integrations.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-end">
                                <button
                                    type="submit"
                                    class="btn-primary"
                                    :disabled="form.processing"
                                >
                                    Update User
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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
    entryTypes: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    is_admin: props.user.is_admin,
    is_approved: props.user.is_approved,
    entry_type_permissions: props.user.entry_type_permissions || []
});

// API Key Management
const showApiKey = ref(false);
const regenerating = ref(false);

const copyApiKey = async () => {
    try {
        await navigator.clipboard.writeText(props.user.api_key);
        // Show success message (you can implement toast notification if needed)
        alert('API key copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy API key:', err);
        alert('Failed to copy API key to clipboard');
    }
};

const regenerateApiKey = () => {
    if (
        confirm(
            'Are you sure you want to regenerate the API key? This will invalidate the current key and may break existing integrations.'
        )
    ) {
        regenerating.value = true;

        const regenerateForm = useForm({});
        regenerateForm.post(route('admin.users.regenerate-api-key', props.user.id), {
            onSuccess: () => {
                regenerating.value = false;
                alert('API key regenerated successfully!');
                // Reload the page to show the new key
                window.location.reload();
            },
            onError: () => {
                regenerating.value = false;
                alert('Failed to regenerate API key');
            }
        });
    }
};
</script>
