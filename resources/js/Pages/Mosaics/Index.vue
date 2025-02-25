<template>
    <Head title="Mosaics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Mosaics
                </h2>
                <Link
                    :href="route('mosaics.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Create New Mosaic
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="mosaic in mosaics" :key="mosaic.id" 
                        class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ mosaic.title || 'Untitled Mosaic' }}</h3>
                                    <p class="text-gray-600 mt-1">{{ mosaic.description || 'No description' }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <Link :href="route('mosaics.edit', mosaic.id)"
                                        class="text-blue-600 hover:text-blue-800">
                                        Edit
                                    </Link>
                                    <button @click="deleteMosaic(mosaic.id)"
                                        class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-2">
                                <div v-for="item in mosaic.items.slice(0, 3)" :key="item.id"
                                    class="aspect-square bg-gray-100 rounded overflow-hidden">
                                    <img v-if="item.image_path" :src="item.image_path" 
                                        :alt="item.title"
                                        class="w-full h-full object-cover">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    mosaics: Array
});

const deleteMosaic = (id) => {
    if (confirm('Are you sure you want to delete this mosaic?')) {
        router.delete(route('mosaics.destroy', id));
    }
};
</script> 