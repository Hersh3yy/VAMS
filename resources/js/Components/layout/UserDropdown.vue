<script setup lang="ts">
import Dropdown from '@/Components/Base/Dropdown.vue';
import DropdownLink from '@/Components/Base/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative ms-3">
        <div v-if="$page.props.is_impersonating" class="mb-2">
            <Link
                :href="route('impersonate.leave')"
                method="post"
                as="button"
                class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-sm font-medium leading-4 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:outline-none"
            >
                Return to Admin
            </Link>
        </div>
        <Dropdown align="right" width="48">
            <template #trigger>
                <span class="inline-flex rounded-md">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                    >
                        {{ $page.props.auth?.user?.name || 'User' }}

                        <svg
                            class="-me-0.5 ms-2 h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </span>
            </template>

            <template #content>
                <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                <DropdownLink :href="route('logout')" method="post" as="button">
                    Log Out
                </DropdownLink>
            </template>
        </Dropdown>
    </div>
</template>
