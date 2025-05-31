<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-xl font-bold text-gray-800">
                                VAMS Admin
                            </Link>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <Link
                                :href="route('dashboard')"
                                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                :class="{ 'border-indigo-500 text-gray-900': route().current('dashboard') }"
                            >
                                Dashboard
                            </Link>
                            <Link
                                :href="route('albums.index')"
                                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                :class="{ 'border-indigo-500 text-gray-900': route().current('albums.*') }"
                            >
                                Albums
                            </Link>
                            <Link
                                :href="route('mosaics.index')"
                                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                :class="{ 'border-indigo-500 text-gray-900': route().current('mosaics.*') }"
                            >
                                Mosaics
                            </Link>
                        </div>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="ml-3 relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition duration-150 ease-in-out">
                                        <span class="sr-only">Open user menu</span>
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-600 font-medium">{{ auth.user.name[0] }}</span>
                                        </div>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profile
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header class="bg-white shadow" v-if="$slots.header">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/general/Dropdown.vue';
import DropdownLink from '@/Components/general/DropdownLink.vue';

defineProps<{
    auth: {
        user: {
            name: string;
        };
    };
}>();
</script> 