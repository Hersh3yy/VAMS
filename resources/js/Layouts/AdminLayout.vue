<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <Link
                                :href="route('dashboard')"
                                class="text-xl font-bold text-gray-800"
                            >
                                VAMS Admin
                            </Link>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                                :class="{
                                    'border-indigo-500 text-gray-900': route().current('dashboard')
                                }"
                            >
                                Dashboard
                            </Link>
                            <Link
                                :href="route('albums.index')"
                                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                                :class="{
                                    'border-indigo-500 text-gray-900': route().current('albums.*')
                                }"
                            >
                                Albums
                            </Link>
                            <Link
                                :href="route('mosaics.index')"
                                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                                :class="{
                                    'border-indigo-500 text-gray-900': route().current('mosaics.*')
                                }"
                            >
                                Mosaics
                            </Link>
                        </div>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="relative ml-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        class="flex rounded-full border-2 border-transparent text-sm transition duration-150 ease-in-out focus:border-gray-300 focus:outline-none"
                                    >
                                        <span class="sr-only">Open user menu</span>
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200"
                                        >
                                            <span class="font-medium text-gray-600">{{
                                                auth.user.name[0]
                                            }}</span>
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
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
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
import Dropdown from '@/Components/Base/Dropdown.vue';
import DropdownLink from '@/Components/Base/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    auth: {
        user: {
            name: string;
        };
    };
}>();
</script>
