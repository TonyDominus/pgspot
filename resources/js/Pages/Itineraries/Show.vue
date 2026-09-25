<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import MapView from '@/Components/Pg/MapView.vue';
import SeoHead from '@/Components/Pg/SeoHead.vue';
import PoiThumbnail from '@/Components/Pg/PoiThumbnail.vue';

defineProps({
    itinerary: Object,
    stops: Array,
    seo: Object,
});

const difficultyLabel = {
    easy: 'Facile',
    medium: 'Media',
    hard: 'Impegnativa',
};

function mapCenter(stops, itinerary) {
    const first = stops?.[0];
    return first
        ? { lat: first.latitude, lng: first.longitude, zoom: 14 }
        : { lat: 43.1107, lng: 12.3908, zoom: 14 };
}
</script>

<template>
    <SeoHead v-if="seo" :seo="seo" />
    <Head v-else :title="itinerary.title" />

    <AppShell active-nav="routes">
        <header class="relative min-h-48 bg-gradient-to-br from-pg-primary to-pg-primary-dark text-white">
            <img v-if="itinerary.cover_url" :src="itinerary.cover_url" :alt="itinerary.title" class="absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0 bg-black/40" />
            <div class="relative px-4 py-8">
                <Link :href="route('routes')" class="text-sm text-white/80">← Tutti gli itinerari</Link>
                <h1 class="mt-2 text-2xl font-bold">{{ itinerary.title }}</h1>
                <p v-if="itinerary.territory" class="mt-1 text-sm text-white/90">{{ itinerary.territory }}</p>
                <div class="mt-3 flex flex-wrap gap-3 text-xs font-medium">
                    <span>{{ stops.length }} tappe</span>
                    <span v-if="itinerary.duration">{{ itinerary.duration }}</span>
                    <span v-if="itinerary.distance_km">{{ itinerary.distance_km }} km</span>
                    <span v-if="itinerary.difficulty">{{ difficultyLabel[itinerary.difficulty] ?? itinerary.difficulty }}</span>
                </div>
            </div>
        </header>

        <main class="space-y-5 px-4 py-4">
            <p v-if="itinerary.description" class="text-sm text-pg-text">{{ itinerary.description }}</p>

            <Link :href="itinerary.map_href" class="pg-btn-primary inline-flex">Vedi percorso sulla mappa</Link>

            <section v-if="stops.length">
                <h2 class="mb-2 font-semibold">Tappe in ordine</h2>
                <div class="mb-2 h-64 overflow-hidden rounded-2xl">
                    <MapView
                        :pois="stops"
                        :center="mapCenter(stops)"
                        :zoom="14"
                        numbered
                        show-line
                        fit-stops
                    />
                </div>
                <p class="mb-4 text-xs text-pg-muted">La linea unisce le tappe in ordine. Non è un percorso stradale calcolato.</p>
            </section>

            <ol class="space-y-3">
                <li v-for="stop in stops" :key="stop.id" class="flex gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-pg-primary text-sm font-bold text-white">
                        {{ stop.position || stop.stop_number }}
                    </span>
                    <Link :href="route('poi.show', stop.slug)" class="pg-card flex min-w-0 flex-1 gap-3 p-3">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-gray-100">
                            <PoiThumbnail :poi="stop" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium">{{ stop.name }}</p>
                            <p class="text-xs text-pg-muted">
                                <span v-if="stop.primary_category">{{ stop.primary_category.name }}</span>
                                <span v-if="stop.municipality"> · {{ stop.municipality }}</span>
                            </p>
                            <p v-if="stop.note" class="mt-1 text-sm text-pg-text">{{ stop.note }}</p>
                        </div>
                    </Link>
                </li>
            </ol>
        </main>
    </AppShell>
</template>
