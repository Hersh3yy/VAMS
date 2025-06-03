<script setup lang="ts">
import ResponsiveNavLink from '@/Components/general/ResponsiveNavLink.vue';
</script>

<template>
    <div class="sm:hidden bg-black border-t border-yellow-500">
        <div class="space-y-1 pb-3 pt-2">
            <ResponsiveNavLink
                :href="route('dashboard')"
                :active="route().current('dashboard')"
                class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
            >
                Dashboard
            </ResponsiveNavLink>
            <ResponsiveNavLink
                :href="route('albums.index')"
                :active="route().current('albums.index')"
                class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
            >
                Albums
            </ResponsiveNavLink>
            <ResponsiveNavLink
                v-if="$page.props.auth?.user?.is_admin"
                :href="route('admin.dashboard')"
                :active="route().current('admin.*')"
                class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
            >
                Admin
            </ResponsiveNavLink>
            <ResponsiveNavLink
                :href="route('mosaics.index')"
                :active="route().current('mosaics.*')"
                class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
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
                    class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
                >
                    Profile
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-yellow-400 hover:text-yellow-300 border-yellow-500"
                >
                    Log Out
                </ResponsiveNavLink>
            </div>
        </div>
    </div>
</template> 