<template>
    <Head title="Mosaics" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-header">
                <h2 class="page-title">Mosaics</h2>
                <Link :href="route('mosaics.create')" class="btn-primary"> Create New Mosaic </Link>
            </div>
        </template>

        <div class="content-wrapper">
            <div class="content-container">
                <MosaicContentWrapper
                    :mosaics="entities"
                    :create-route="route('mosaics.create')"
                    :get-item-route="(mosaic: Mosaic) => route('mosaics.show', mosaic.id)"
                    :actions="getMosaicActions"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import MosaicContentWrapper from '@/Components/organisms/MosaicContentWrapper.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Mosaic } from '@/types/mosaic';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    entities: Mosaic[];
}>();

const deleteMosaic = (mosaic: Mosaic) => {
    if (confirm(`Are you sure you want to delete "${mosaic.title}"?`)) {
        router.delete(route('mosaics.destroy', mosaic.id));
    }
};

const getMosaicActions = (mosaic: Mosaic) => [
    {
        label: 'View & Edit',
        handler: () => router.visit(route('mosaics.show', mosaic.id)),
        icon: 'edit' as const,
        variant: 'secondary' as const
    },
    {
        label: 'Delete Mosaic',
        handler: () => deleteMosaic(mosaic),
        icon: 'trash' as const,
        variant: 'danger' as const
    }
];
</script>
