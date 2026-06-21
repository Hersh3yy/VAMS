<script setup lang="ts">
import BaseButton from '@/Components/Base/Button.vue';
import ConfirmationDialog from '@/Components/molecules/ConfirmationDialog.vue';
import type { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<PageProps>();

const copied = ref(false);
const showRegenerateDialog = ref(false);

// Plain-text key is only available once, via the flash after regeneration
const newApiKey = computed(() => page.props.flash?.new_api_key);

const regenerateApiKey = (): void => {
    showRegenerateDialog.value = false;

    router.post(
        route('profile.update'),
        {
            _method: 'PATCH',
            regenerate_api_key: true,
            name: page.props.auth.user.name,
            email: page.props.auth.user.email
        },
        {
            preserveScroll: true
        }
    );
};

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
</script>

<template>
    <div class="mt-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">API Key</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Your API key authenticates requests to the API. Keys are stored hashed, so the
            plain-text key is only shown once after regeneration - copy it somewhere safe.
        </p>

        <div v-if="newApiKey" class="mt-4">
            <div class="flex items-center gap-2">
                <input
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

        <div class="mt-4">
            <BaseButton variant="danger" size="sm" @click="showRegenerateDialog = true">
                Regenerate API Key
            </BaseButton>
        </div>

        <ConfirmationDialog
            :show="showRegenerateDialog"
            title="Regenerate API Key?"
            message="This will invalidate your current key and may break existing integrations. Are you sure?"
            confirm-text="Regenerate"
            cancel-text="Cancel"
            @confirm="regenerateApiKey"
            @cancel="showRegenerateDialog = false"
        />
    </div>
</template>
