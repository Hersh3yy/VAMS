<template>
    <Head title="Albums" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <h1 class="page-title">Albums</h1>
                <Link :href="route('albums.create')" class="btn-primary"> Create Album </Link>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <AlbumContentWrapper
                    :albums="entities"
                    :create-route="route('albums.create')"
                    :get-item-route="(album: Album) => route('albums.show', album.id)"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AlbumContentWrapper from '@/Components/organisms/AlbumContentWrapper.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Album } from '@/types/album';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    entities: Album[];
}>();
</script>
