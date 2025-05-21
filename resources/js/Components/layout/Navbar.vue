<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import UserDropdown from './UserDropdown.vue';

defineProps<{
    showingNavigationDropdown: boolean;
    toggleNavigation: () => void;
}>();
</script>

<template>
    <nav class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="shrink-0 flex items-center">
                        <Link :href="route('dashboard')">
                            <img 
                                v-if="$page.props.auth?.user?.logo_url" 
                                :src="$page.props.auth.user.logo_url" 
                                class="block h-9 w-auto"
                                alt="Custom Logo"
                            />
                            <ApplicationLogo v-else class="block h-9 w-auto fill-current text-gray-800" />
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                        <NavLink
                            :href="route('albums.index')"
                            :active="route().current('albums.index')"
                        >
                            Albums
                        </NavLink>
                        <NavLink
                            v-if="$page.props.auth?.user?.is_admin"
                            :href="route('admin.dashboard')"
                            :active="route().current('admin.*')"
                        >
                            Admin
                        </NavLink>
                        <NavLink
                            :href="route('mosaics.index')"
                            :active="route().current('mosaics.*')"
                        >
                            Mosaics
                        </NavLink>
                    </div>
                </div>

                <div class="hidden sm:ms-6 sm:flex sm:items-center">
                    <UserDropdown />
                </div>

                <!-- Hamburger -->
                <div class="-me-2 flex items-center sm:hidden">
                    <button
                        @click="toggleNavigation"
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                    >
                        <svg
                            class="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                :class="{
                                    hidden: showingNavigationDropdown,
                                    'inline-flex': !showingNavigationDropdown,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{
                                    hidden: !showingNavigationDropdown,
                                    'inline-flex': showingNavigationDropdown,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>
</template> 