<script setup lang="ts">
import { ref, provide } from 'vue';
import Navbar from '@/Components/layout/Navbar.vue';
import MobileNav from '@/Components/layout/MobileNav.vue';
import ThemeStyles from '@/Components/layout/ThemeStyles.vue';
import ErrorModal from '@/Components/albums/ErrorModal.vue';

const showingNavigationDropdown = ref(false);
const errorModal = ref<InstanceType<typeof ErrorModal> | null>(null);

const toggleNavigation = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

const showError = (message: string) => {
    errorModal.value?.showError(message);
};

// Provide the showError function to all child components
provide('showError', showError);
</script>

<template>
    <ThemeStyles>
        <div class="min-h-screen bg-gray-100">
            <Navbar 
                :showing-navigation-dropdown="showingNavigationDropdown"
                :toggle-navigation="toggleNavigation"
            />

            <MobileNav
                v-show="showingNavigationDropdown"
            />

            <!-- Page Heading -->
            <header
                class="bg-white shadow dark:bg-gray-800"
                v-if="$slots.header"
            >
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
    </ThemeStyles>
</template>
