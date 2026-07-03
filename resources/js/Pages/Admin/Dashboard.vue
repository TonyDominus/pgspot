<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Pannello Admin
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">Utenti registrati</p>
                        <p class="mt-2 text-3xl font-bold">{{ stats.users }}</p>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">POI pubblicati</p>
                        <p class="mt-2 text-3xl font-bold">{{ stats.pois_published }}</p>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">POI in attesa</p>
                        <p class="mt-2 text-3xl font-bold text-amber-600">{{ stats.pois_pending }}</p>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">Contributi da moderare</p>
                        <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.contributions_pending }}</p>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">Eventi pubblicati</p>
                        <p class="mt-2 text-3xl font-bold">{{ stats.events_published }}</p>
                    </div>
                </div>

                <div class="mt-8 rounded-lg bg-white p-6 shadow">
                    <h3 class="font-semibold text-gray-800">Prossimi step</h3>
                    <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-gray-600">
                        <li>Moderazione contributi utente</li>
                        <li>Gestione eventi e vetrine</li>
                        <li>Mappa Leaflet interattiva</li>
                        <li>Pannello superadmin per impostazioni globali</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
