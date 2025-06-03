<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/general/ApplicationLogo.vue';
import NavLink from '@/Components/general/NavLink.vue';
import UserDropdown from './UserDropdown.vue';

defineProps<{
    showingNavigationDropdown: boolean;
    toggleNavigation: () => void;
}>();
</script>

<template>
    <nav class="bg-black border-b border-yellow-500">
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
                            <ApplicationLogo v-else class="block h-9 w-auto fill-current text-yellow-500" />
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                        <NavLink
                            :href="route('albums.index')"
                            :active="route().current('albums.index')"
                            class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
                        >
                            Albums
                        </NavLink>
                        <NavLink
                            v-if="$page.props.auth?.user?.is_admin"
                            :href="route('admin.dashboard')"
                            :active="route().current('admin.*')"
                            class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
                        >
                            Admin
                        </NavLink>
                        <NavLink
                            :href="route('mosaics.index')"
                            :active="route().current('mosaics.*')"
                            class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
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
                        class="inline-flex items-center justify-center rounded-md p-2 text-yellow-400 transition duration-150 ease-in-out hover:bg-gray-900 hover:text-yellow-300 focus:bg-gray-900 focus:text-yellow-300 focus:outline-none"
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