<template>
    <div class="border-t border-yellow-500 bg-black sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <ResponsiveNavLink
                :href="route('dashboard')"
                :active="route().current('dashboard')"
                class="border-yellow-500 text-yellow-400 hover:text-yellow-300"
            >
                Dashboard
            </ResponsiveNavLink>
            <ResponsiveNavLink
                :href="route('albums.index')"
                :active="route().current('albums.index')"
                class="border-yellow-500 text-yellow-400 hover:text-yellow-300"
            >
                Albums
            </ResponsiveNavLink>
            
            <!-- Dynamic Entry Type Links -->
            <ResponsiveNavLink
                v-for="entryType in $page.props.auth?.user?.allowed_entry_types || []"
                :key="entryType.slug"
                :href="route('entries.index', { type: entryType.slug })"
                :active="route().current('entries.index') && $page.url.includes(`type=${entryType.slug}`)"
                class="border-yellow-500 text-yellow-400 hover:text-yellow-300"
            >
                {{ entryType.name }}
            </ResponsiveNavLink>
            <ResponsiveNavLink
                v-if="$page.props.auth?.user?.is_admin"
                :href="route('admin.dashboard')"
                :active="route().current('admin.*')"
                class="border-yellow-500 text-yellow-400 hover:text-yellow-300"
            >
                Admin
            </ResponsiveNavLink>
            <ResponsiveNavLink
                :href="route('mosaics.index')"
                :active="route().current('mosaics.*')"
                class="border-yellow-500 text-yellow-400 hover:text-yellow-300"
            >
                Mosaics
            </ResponsiveNavLink>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-yellow-500 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-yellow-400">
                    {{ $page.props.auth?.user?.name || 'User' }}
                </div>
                <div class="text-sm font-medium text-yellow-300">
                    {{ $page.props.auth?.user?.email || '' }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <ResponsiveNavLink
                    :href="route('profile.edit')"
                    class="border-yellow-500 text-yellow-400 hover:text-yellow-300"
                >
                    Profile
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="border-yellow-500 text-yellow-400 hover:text-yellow-300"
                >
                    Log Out
                </ResponsiveNavLink>
                <button
                    type="button"
                    class="flex w-full items-center gap-2 border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-yellow-400 transition duration-150 ease-in-out hover:border-yellow-500 hover:bg-gray-900 hover:text-yellow-300 focus:outline-none"
                    @click="toggleDarkMode"
                >
                    <Icon :name="isDark ? 'sun' : 'moon'" size="sm" />
                    {{ isDark ? 'Light Mode' : 'Dark Mode' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Base/Icon.vue';
import ResponsiveNavLink from '@/Components/Base/ResponsiveNavLink.vue';
import { useDarkMode } from '@/composables/shared/useDarkMode';

const { isDark, toggleDarkMode } = useDarkMode();
</script>
