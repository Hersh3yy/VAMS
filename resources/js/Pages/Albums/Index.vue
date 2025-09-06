<template>
    <Head title="Albums" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <h2 class="page-title">Albums</h2>
                <Link :href="route('albums.create')" class="btn-primary">
                    Create Album
                </Link>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <AlbumContentWrapper
                    :albums="albums"
                    :create-route="route('albums.create')"
                    :get-item-route="(album: Album) => route('albums.show', album.id)"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AlbumContentWrapper from '@/Components/organisms/AlbumContentWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import type { Album } from '@/types/album';

defineProps<{
    albums: Album[];
}>();
</script>
