<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

const regenerateApiKey = () => {
    if (confirm('Are you sure you want to regenerate your API key? This will invalidate your current key.')) {
        router.post(route('profile.update'), {
            _method: 'PATCH',
            regenerate_api_key: true
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // The page will refresh with the new API key
            },
        });
    }
};
</script>

<template>
    <div class="mt-6">
        <h3 class="text-lg font-medium text-gray-900">API Key</h3>
        <p class="mt-1 text-sm text-gray-600">
            Your API key is used to authenticate requests to the API. Keep it safe but don't worry too much about it being exposed.
        </p>
        
        <div class="mt-4 flex items-center gap-4">
            <div class="flex-1">
                <input
                    type="text"
                    :value="user.api_key"
                    readonly
                    class="block w-full rounded-md border-gray-300 bg-gray-50 text-gray-500"
                />
            </div>
            <button
                type="button"
                @click="regenerateApiKey"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Regenerate
            </button>
        </div>
    </div>
</template> 