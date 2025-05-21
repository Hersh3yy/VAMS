<script setup lang="ts">
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
</script>

<template>
    <div class="sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <ResponsiveNavLink
                :href="route('dashboard')"
                :active="route().current('dashboard')"
            >
                Dashboard
            </ResponsiveNavLink>
            <ResponsiveNavLink
                :href="route('albums.index')"
                :active="route().current('albums.index')"
            >
                Albums
            </ResponsiveNavLink>
            <ResponsiveNavLink
                v-if="$page.props.auth?.user?.is_admin"
                :href="route('admin.dashboard')"
                :active="route().current('admin.*')"
            >
                Admin
            </ResponsiveNavLink>
            <ResponsiveNavLink
                :href="route('mosaics.index')"
                :active="route().current('mosaics.*')"
            >
                Mosaics
            </ResponsiveNavLink>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                    {{ $page.props.auth?.user?.name || 'User' }}
                </div>
                <div class="text-sm font-medium text-gray-500">
                    {{ $page.props.auth?.user?.email || '' }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <ResponsiveNavLink :href="route('profile.edit')">
                    Profile
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                >
                    Log Out
                </ResponsiveNavLink>
            </div>
        </div>
    </div>
</template> 