<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    pois: Array,
});
</script>

<template>
    <Head title="Preferiti" />

    <AppShell active-nav="favorites">
        <header class="bg-pg-surface px-4 py-5 shadow-sm">
            <h1 class="text-xl font-bold text-pg-text">Preferiti</h1>
            <p class="text-sm text-pg-muted">I luoghi che hai salvato</p>
        </header>

        <main class="space-y-3 px-4 py-4">
            <div v-if="pois.length === 0" class="flex flex-col items-center py-16 text-center">
                <PgIcon name="heart" class="mb-3 h-12 w-12 text-gray-200" />
                <p class="text-pg-muted">Nessun preferito ancora.</p>
                <p class="mt-1 text-sm text-pg-muted">Esplora la mappa e salva i tuoi luoghi preferiti.</p>
            </div>

            <PoiListCard v-for="poi in pois" :key="poi.id" :poi="poi" />
        </main>
    </AppShell>
</template>
