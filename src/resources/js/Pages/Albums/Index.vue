<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    albums: Array
});
</script>

<template>
    <Head title="Albums" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Albums</h2>
                <Link 
                    :href="route('albums.create')" 
                    class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700"
                >
                    Create Album
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="album in albums" :key="album.id" 
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <Link :href="route('albums.show', album.id)">
                            <div class="aspect-w-16 aspect-h-9">
                                <img 
                                    :src="album.cover_image_path || '/placeholder.jpg'" 
                                    :alt="album.title"
                                    class="object-cover w-full h-full"
                                >
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-semibold">{{ album.title }}</h3>
                                <p class="text-gray-600 mt-2">{{ album.description }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 