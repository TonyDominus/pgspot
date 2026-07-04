<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    itineraries: Array,
});
</script>

<template>
    <Head title="Itinerari" />

    <AppShell active-nav="routes">
        <header class="bg-pg-surface px-4 py-5 shadow-sm">
            <h1 class="text-xl font-bold text-pg-text">Itinerari</h1>
            <p class="text-sm text-pg-muted">Percorsi consigliati a Perugia</p>
        </header>

        <main class="space-y-4 px-4 py-4">
            <Link
                v-for="item in itineraries"
                :key="item.id"
                :href="route('itineraries.show', item.slug)"
                class="pg-card block overflow-hidden transition hover:shadow-md"
            >
                <div class="h-28 bg-gradient-to-br from-pg-primary to-pg-primary-dark p-4 text-white">
                    <PgIcon name="route" class="mb-2 h-6 w-6 opacity-80" />
                    <h2 class="font-semibold">{{ item.title }}</h2>
                </div>
                <div class="p-4">
                    <p class="text-sm text-pg-muted">{{ item.description }}</p>
                    <div class="mt-3 flex gap-4 text-xs font-medium text-pg-primary">
                        <span>{{ item.stops?.length ?? item.poi_ids?.length ?? 0 }} tappe</span>
                        <span v-if="item.duration">{{ item.duration }}</span>
                    </div>
                </div>
            </Link>

            <p v-if="!itineraries?.length" class="py-8 text-center text-sm text-pg-muted">
                Nessun itinerario disponibile al momento.
            </p>
        </main>
    </AppShell>
</template>
