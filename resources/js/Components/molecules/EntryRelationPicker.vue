<template>
    <div class="space-y-2">
        <BaseLabel v-if="label" :text="label" :for-id="inputId" :required="required" />

        <!-- Selected entries as removable chips -->
        <ul v-if="selectedIds.length" class="flex flex-wrap gap-2">
            <li
                v-for="id in visibleSelectedIds"
                :key="id"
                class="inline-flex max-w-xs items-center gap-1 rounded-full py-1 pl-3 pr-1 text-sm"
                :class="
                    titleFor(id)
                        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                "
                :title="titleFor(id) ?? id"
            >
                <span class="truncate">{{
                    titleFor(id) ?? (isMissing(id) ? 'Unavailable entry' : 'Loading…')
                }}</span>
                <button
                    type="button"
                    class="rounded-full p-0.5 hover:bg-black/10 dark:hover:bg-white/10"
                    :aria-label="`Remove ${titleFor(id) ?? 'entry'}`"
                    @click="remove(id)"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </li>
            <li v-if="selectedIds.length > previewCount">
                <button
                    type="button"
                    class="px-1 py-1 text-sm text-secondary hover:text-indigo-800 dark:hover:text-indigo-300"
                    @click="showAllSelected = !showAllSelected"
                >
                    {{ showAllSelected ? 'Show fewer' : `Show all ${selectedIds.length}` }}
                </button>
            </li>
        </ul>

        <!-- Search box + results -->
        <div class="relative">
            <input
                :id="inputId"
                v-model="query"
                type="search"
                autocomplete="off"
                role="combobox"
                :aria-expanded="open"
                :aria-controls="`${inputId}-results`"
                :disabled="searchDisabled"
                :placeholder="searchPlaceholder"
                class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                :class="[
                    error
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600'
                        : 'border-gray-300 focus:border-secondary focus:ring-secondary dark:border-gray-600 dark:focus:ring-indigo-400',
                    searchDisabled ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-800' : ''
                ]"
                @input="onInput"
                @focus="openResults"
                @blur="open = false"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="pickHighlighted"
                @keydown.esc="open = false"
            />

            <ul
                v-if="open"
                :id="`${inputId}-results`"
                role="listbox"
                class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 text-sm shadow-lg dark:border-gray-600 dark:bg-gray-800"
            >
                <li v-if="loading" class="px-3 py-2 text-gray-500 dark:text-gray-400">
                    Searching…
                </li>
                <li v-else-if="searchError" class="px-3 py-2 text-red-600 dark:text-red-400">
                    {{ searchError }}
                </li>
                <li
                    v-else-if="availableResults.length === 0"
                    class="px-3 py-2 text-gray-500 dark:text-gray-400"
                >
                    No matching entries
                </li>
                <li
                    v-for="(item, index) in availableResults"
                    v-else
                    :key="item.id"
                    role="option"
                    :aria-selected="index === highlighted"
                    class="cursor-pointer truncate px-3 py-2 text-gray-900 dark:text-gray-100"
                    :class="
                        index === highlighted
                            ? 'bg-indigo-50 dark:bg-gray-700'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700'
                    "
                    @mousedown.prevent="add(item)"
                    @mouseenter="highlighted = index"
                >
                    {{ item.title }}
                </li>
            </ul>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">{{ hint }}</p>

        <BaseErrorMessage :error="error" />
    </div>
</template>

<script setup lang="ts">
import BaseErrorMessage from '@/Components/Base/ErrorMessage.vue';
import BaseLabel from '@/Components/Base/Label.vue';
import { useEntryLookup } from '@/composables/entries/useEntryLookup';
import type { EntryLookupItem } from '@/types/entryField';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, useId, watch } from 'vue';

interface Props {
    modelValue: unknown;
    entryTypeSlug?: string;
    id?: string;
    label?: string;
    required?: boolean;
    min?: number;
    max?: number;
    error?: string;
    /** Hide this entry id from results (e.g. the entry being edited). */
    excludeId?: string | null;
    previewCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    entryTypeSlug: undefined,
    id: undefined,
    label: undefined,
    min: undefined,
    max: undefined,
    error: undefined,
    excludeId: null,
    previewCount: 24
});

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const { titleFor, isMissing, resolve, search, remember } = useEntryLookup();

const autoId = useId();
const inputId = computed(() => props.id ?? `entry-relation-${autoId}`);
const query = ref('');
const results = ref<EntryLookupItem[]>([]);
const open = ref(false);
const loading = ref(false);
const searchError = ref('');
const highlighted = ref(0);
const showAllSelected = ref(false);
let requestSeq = 0;

const selectedIds = computed<string[]>(() =>
    Array.isArray(props.modelValue) ? props.modelValue.map(String).filter(id => id !== '') : []
);

const visibleSelectedIds = computed(() =>
    showAllSelected.value ? selectedIds.value : selectedIds.value.slice(0, props.previewCount)
);

const maxReached = computed(() => !!props.max && selectedIds.value.length >= props.max);
const searchDisabled = computed(() => !props.entryTypeSlug || maxReached.value);

const searchPlaceholder = computed(() => {
    if (maxReached.value) {
        return `Maximum of ${props.max} reached`;
    }
    return props.entryTypeSlug ? `Search ${props.entryTypeSlug} entries…` : 'Search unavailable';
});

const hint = computed(() => {
    if (!props.entryTypeSlug) {
        return 'This field has no entry type configured (entry_type_slug).';
    }
    let text = `${selectedIds.value.length} selected`;
    if (props.max) {
        text += ` of max ${props.max}`;
    }
    if (props.min && selectedIds.value.length < props.min) {
        text += ` · select at least ${props.min}`;
    }
    return text;
});

const availableResults = computed(() =>
    results.value.filter(
        item => !selectedIds.value.includes(item.id) && item.id !== props.excludeId
    )
);

watch(visibleSelectedIds, ids => resolve(ids), { immediate: true });

const runSearch = async (text: string): Promise<void> => {
    if (!props.entryTypeSlug) {
        return;
    }

    const seq = ++requestSeq;
    loading.value = true;
    searchError.value = '';

    try {
        const found = await search(props.entryTypeSlug, text);
        if (seq === requestSeq) {
            results.value = found;
            highlighted.value = 0;
        }
    } catch {
        if (seq === requestSeq) {
            searchError.value = 'Search failed, please try again.';
        }
    } finally {
        if (seq === requestSeq) {
            loading.value = false;
        }
    }
};

const debouncedSearch = useDebounceFn((text: string) => runSearch(text), 250);

// @input rather than a watcher, so clearing the box after a pick doesn't reopen the list.
const onInput = (): void => {
    open.value = true;
    debouncedSearch(query.value.trim());
};

const openResults = (): void => {
    if (searchDisabled.value) {
        return;
    }
    open.value = true;
    if (results.value.length === 0 && !loading.value) {
        runSearch(query.value.trim());
    }
};

const move = (step: number): void => {
    if (!open.value) {
        openResults();
        return;
    }
    const count = availableResults.value.length;
    if (count > 0) {
        highlighted.value = (highlighted.value + step + count) % count;
    }
};

const add = (item: EntryLookupItem): void => {
    if (maxReached.value || selectedIds.value.includes(item.id)) {
        return;
    }
    remember([item]);
    emit('update:modelValue', [...selectedIds.value, item.id]);
    query.value = '';
    highlighted.value = 0;
    if (props.max && selectedIds.value.length + 1 >= props.max) {
        open.value = false;
    }
};

const pickHighlighted = (): void => {
    const item = availableResults.value[highlighted.value];
    if (open.value && item) {
        add(item);
    }
};

const remove = (id: string): void => {
    emit(
        'update:modelValue',
        selectedIds.value.filter(selected => selected !== id)
    );
};
</script>
