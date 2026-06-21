<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="`Edit User: ${user.name}`">
                <template #actions>
                    <Link :href="route('admin.users.index')">
                        <BaseButton variant="secondary">Back to Users</BaseButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="mb-4 space-y-2">
                    <FlashMessage :message="page.props.flash?.success" type="success" />
                    <FlashMessage :message="page.props.flash?.error" type="error" />
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <form @submit.prevent="form.patch(route('admin.users.update', user.id))" class="p-6">
                        <FormField
                            id="name"
                            label="Name"
                            v-model="form.name"
                            :error="form.errors.name"
                            required
                            autofocus
                        />

                        <FormField
                            id="email"
                            label="Email"
                            type="email"
                            v-model="form.email"
                            :error="form.errors.email"
                            required
                        />

                        <PasswordField
                            id="password"
                            label="Password"
                            v-model="form.password"
                            :error="form.errors.password"
                            autocomplete="new-password"
                            hint="Leave blank to keep current password"
                        />

                        <fieldset class="mb-6 mt-6">
                            <legend class="sr-only">User flags</legend>
                            <div class="flex flex-wrap gap-x-8 gap-y-3">
                                <Checkbox id="is_admin" label="Admin User" v-model="form.is_admin" />
                                <Checkbox id="is_approved" label="Approved" v-model="form.is_approved" />
                            </div>
                            <BaseErrorMessage :error="form.errors.is_admin" />
                            <BaseErrorMessage :error="form.errors.is_approved" />
                        </fieldset>

                        <fieldset class="mb-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                            <legend class="sr-only">Entry type permissions</legend>
                            <h3 class="mb-1 text-lg font-medium text-gray-900 dark:text-gray-100">
                                Entry Type Permissions
                            </h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                Control which entry types this user can access. Users can only
                                access checked entry types.
                            </p>
                            <div class="space-y-3">
                                <div
                                    v-for="entryType in entryTypes"
                                    :key="entryType.id"
                                    class="flex items-center"
                                >
                                    <input
                                        :id="`entry_type_${entryType.slug}`"
                                        type="checkbox"
                                        :value="entryType.slug"
                                        v-model="form.entry_type_permissions"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600"
                                    />
                                    <label
                                        :for="`entry_type_${entryType.slug}`"
                                        class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {{ entryType.name }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            ({{ entryType.slug }})
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <BaseErrorMessage :error="form.errors.entry_type_permissions" />
                        </fieldset>

                        <div class="mb-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                            <h3 class="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">
                                API Key Management
                            </h3>
                            <div class="space-y-3">
                                <div v-if="newApiKey">
                                    <BaseLabel text="New API Key" for-id="api_key" />
                                    <div class="mt-1 flex items-center gap-2">
                                        <input
                                            id="api_key"
                                            type="text"
                                            :value="newApiKey"
                                            readonly
                                            class="block w-full flex-1 rounded-md border-yellow-400 px-3 py-2 font-mono text-sm shadow-sm dark:border-yellow-500 dark:bg-gray-700 dark:text-gray-200"
                                        />
                                        <BaseButton variant="secondary" size="sm" @click="copyApiKey">
                                            {{ copied ? 'Copied!' : 'Copy' }}
                                        </BaseButton>
                                    </div>
                                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-400">
                                        Copy this key now - it will not be shown again.
                                    </p>
                                </div>
                                <p v-else class="text-sm text-gray-600 dark:text-gray-400">
                                    {{
                                        user.has_api_key
                                            ? 'An API key is set for this user. Keys are stored hashed and cannot be viewed - regenerate to issue a new one.'
                                            : 'No API key set. Regenerate to issue one.'
                                    }}
                                </p>
                                <div class="flex flex-wrap items-center gap-3">
                                    <BaseButton
                                        variant="danger"
                                        size="sm"
                                        :loading="regenerateForm.processing"
                                        @click="showRegenerateDialog = true"
                                    >
                                        Regenerate API Key
                                    </BaseButton>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        This will invalidate the current key. Make sure to update
                                        any integrations.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <Link :href="route('admin.users.index')">
                                <BaseButton variant="secondary">Cancel</BaseButton>
                            </Link>
                            <BaseButton
                                type="submit"
                                variant="primary"
                                :disabled="form.processing"
                                :loading="form.processing"
                            >
                                Update User
                            </BaseButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationDialog
            :show="showRegenerateDialog"
            title="Regenerate API Key?"
            message="This will invalidate the current key and may break existing integrations. Are you sure?"
            confirm-text="Regenerate"
            cancel-text="Cancel"
            @confirm="regenerateApiKey"
            @cancel="showRegenerateDialog = false"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import BaseButton from '@/Components/Base/Button.vue';
import BaseErrorMessage from '@/Components/Base/ErrorMessage.vue';
import BaseLabel from '@/Components/Base/Label.vue';
import FlashMessage from '@/Components/Base/FlashMessage.vue';
import Checkbox from '@/Components/atoms/Checkbox.vue';
import ConfirmationDialog from '@/Components/molecules/ConfirmationDialog.vue';
import FormField from '@/Components/molecules/FormField.vue';
import PasswordField from '@/Components/molecules/PasswordField.vue';
import PageHeader from '@/Components/molecules/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface EntryTypeOption {
    id: string;
    name: string;
    slug: string;
}

interface EditableUser {
    id: string;
    name: string;
    email: string;
    is_admin: boolean;
    is_approved: boolean;
    entry_type_permissions: string[] | null;
    has_api_key: boolean;
}

const page = usePage<PageProps>();

const props = withDefaults(
    defineProps<{
        user: EditableUser;
        entryTypes?: EntryTypeOption[];
    }>(),
    {
        entryTypes: () => []
    }
);

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    is_admin: props.user.is_admin,
    is_approved: props.user.is_approved,
    entry_type_permissions: props.user.entry_type_permissions || []
});

const copied = ref(false);
const showRegenerateDialog = ref(false);
const regenerateForm = useForm({});

// Plain-text key is only available once, via the flash after regeneration
const newApiKey = computed(() => page.props.flash?.new_api_key as string | undefined);

const copyApiKey = async (): Promise<void> => {
    if (!newApiKey.value) {
        return;
    }

    await navigator.clipboard.writeText(newApiKey.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const regenerateApiKey = (): void => {
    showRegenerateDialog.value = false;
    regenerateForm.post(route('admin.users.regenerate-api-key', props.user.id), {
        preserveScroll: true
    });
};
</script>
