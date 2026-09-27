<template>
    <div class="space-y-2">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ ids.length }} {{ ids.length === 1 ? 'entry' : 'entries' }}
        </p>

        <ul class="flex flex-wrap gap-2">
            <li v-for="id in visibleIds" :key="id">
                <Link
                    v-if="titleFor(id)"
                    :href="route('entries.show', id)"
                    class="inline-flex max-w-xs items-center truncate rounded-full bg-indigo-50 px-3 py-1 text-sm text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/40 dark:text-indigo-200 dark:hover:bg-indigo-900/70"
                    :title="titleFor(id) ?? undefined"
                >
                    {{ titleFor(id) }}
                </Link>
                <span
                    v-else
                    class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                    :title="id"
                >
                    {{ isMissing(id) ? 'Unavailable entry' : 'Loading…' }}
                </span>
            </li>
        </ul>

        <button
            v-if="ids.length > previewCount"
            type="button"
            class="text-sm text-secondary hover:text-indigo-800 dark:hover:text-indigo-300"
            @click="showAll = !showAll"
        >
            {{ showAll ? 'Show fewer' : `Show all ${ids.length}` }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { useEntryLookup } from '@/composables/entries/useEntryLookup';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    value: unknown;
    previewCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    previewCount: 24
});

const { titleFor, isMissing, resolve } = useEntryLookup();
const showAll = ref(false);

const ids = computed<string[]>(() => {
    const raw = Array.isArray(props.value) ? props.value : props.value ? [props.value] : [];
    return raw.map(String).filter(id => id !== '');
});

const visibleIds = computed(() =>
    showAll.value ? ids.value : ids.value.slice(0, props.previewCount)
);

// Only fetch what is on screen; "Show all" fetches the rest.
watch(visibleIds, current => resolve(current), { immediate: true });
</script>
