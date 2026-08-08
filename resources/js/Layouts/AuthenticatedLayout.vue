<script setup lang="ts">
import ErrorModal from '@/Components/albums/ErrorModal.vue';
import MobileNav from '@/Components/layout/MobileNav.vue';
import Navbar from '@/Components/layout/Navbar.vue';
import { useUserTheme } from '@/composables/shared/useUserTheme';
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { provide, ref, watch } from 'vue';

const showingNavigationDropdown = ref(false);
const errorModal = ref<InstanceType<typeof ErrorModal> | null>(null);

const toggleNavigation = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

const showError = (message: string) => {
    errorModal.value?.showError(message);
};

const { themeStyle } = useUserTheme();

// Provide the showError function to all child components
provide('showError', showError);

// Surface server-flashed errors (e.g. plan limit reached) app-wide without
// every page needing its own display logic.
const page = usePage<PageProps>();

watch(
    () => page.props.flash?.error,
    error => {
        if (error) {
            showError(error);
        }
    }
);
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900" :style="themeStyle">
        <Navbar
            :showing-navigation-dropdown="showingNavigationDropdown"
            :toggle-navigation="toggleNavigation"
        />

        <MobileNav v-show="showingNavigationDropdown" />

        <!-- Page Heading -->
        <header class="bg-white shadow dark:bg-gray-800" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Error Modal -->
        <ErrorModal ref="errorModal" />
    </div>
</template>
