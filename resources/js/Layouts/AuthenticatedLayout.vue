<script setup lang="ts">
import ErrorModal from '@/Components/albums/ErrorModal.vue';
import MobileNav from '@/Components/layout/MobileNav.vue';
import Navbar from '@/Components/layout/Navbar.vue';
import { PageProps, User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, provide, ref, watch } from 'vue';

const showingNavigationDropdown = ref(false);
const errorModal = ref<InstanceType<typeof ErrorModal> | null>(null);
const page = usePage<PageProps>();

const toggleNavigation = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

const showError = (message: string) => {
    errorModal.value?.showError(message);
};

// Theme handling
const userThemeStyle = computed(() => {
    const user = page.props.auth?.user as User;
    if (!user) return { '--primary-color': '#000000', '--secondary-color': '#EAB308' };

    const settings = user.album_display_settings || {};
    const mainColor = settings.main_color || '#000000'; // Default black
    const secondaryColor = settings.secondary_color || '#EAB308'; // Default gold

    return {
        '--primary-color': mainColor,
        '--secondary-color': secondaryColor
    };
});

// Handle dark mode
onMounted(() => {
    document.documentElement.classList.add('dark');
});

// Update CSS variables when theme changes
watch(
    () => userThemeStyle.value,
    newStyle => {
        Object.entries(newStyle).forEach(([key, value]) => {
            document.documentElement.style.setProperty(key, value);
        });
    },
    { immediate: true }
);

// Provide the showError function to all child components
provide('showError', showError);
</script>

<template>
    <div class="min-h-screen bg-gray-100" :style="userThemeStyle">
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
