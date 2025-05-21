<script setup lang="ts">
import { ref } from 'vue';
import Navbar from '@/Components/layout/Navbar.vue';
import MobileNav from '@/Components/layout/MobileNav.vue';
import ThemeStyles from '@/Components/layout/ThemeStyles.vue';

const showingNavigationDropdown = ref(false);
const toggleNavigation = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};
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
        </div>
    </ThemeStyles>
</template>
