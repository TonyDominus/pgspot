<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    itinerary: Object,
    stops: Array,
});
</script>

<template>
    <Head :title="itinerary.title" />

    <AppShell active-nav="routes">
        <header class="bg-gradient-to-br from-pg-primary to-pg-primary-dark px-4 py-8 text-white">
            <Link :href="route('routes')" class="text-sm text-white/80">← Tutti gli itinerari</Link>
            <h1 class="mt-2 text-2xl font-bold">{{ itinerary.title }}</h1>
            <p class="mt-2 text-sm text-white/90">{{ itinerary.description }}</p>
            <div class="mt-3 flex gap-4 text-xs font-medium">
                <span>{{ stops.length }} tappe</span>
                <span v-if="itinerary.duration">{{ itinerary.duration }}</span>
            </div>
        </header>

        <main class="space-y-3 px-4 py-4">
            <div
                v-for="(stop, index) in stops"
                :key="stop.id"
                class="flex gap-3"
            >
                <div class="flex flex-col items-center">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-pg-primary text-sm font-bold text-white">
                        {{ index + 1 }}
                    </span>
                    <div v-if="index < stops.length - 1" class="my-1 w-0.5 flex-1 bg-pg-primary/20" />
                </div>
                <PoiListCard :poi="stop" class="mb-2 flex-1" />
            </div>
        </main>
    </AppShell>
</template>
