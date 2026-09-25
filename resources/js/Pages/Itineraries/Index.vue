<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    itineraries: Array,
});

const difficultyLabel = {
    easy: 'Facile',
    medium: 'Media',
    hard: 'Impegnativa',
};
</script>

<template>
    <Head title="Itinerari" />

    <AppShell active-nav="routes">
        <header class="bg-pg-surface px-4 py-5 shadow-sm">
            <h1 class="text-xl font-bold text-pg-text">Itinerari</h1>
            <p class="text-sm text-pg-muted">Percorsi editoriali tra gli spot di PGSpot</p>
        </header>

        <main class="space-y-4 px-4 py-4">
            <Link
                v-for="item in itineraries"
                :key="item.id"
                :href="route('itineraries.show', item.slug)"
                class="pg-card block overflow-hidden transition hover:shadow-md"
            >
                <div class="relative h-36 bg-gradient-to-br from-pg-primary to-pg-primary-dark">
                    <img v-if="item.cover_url" :src="item.cover_url" :alt="item.title" class="h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
                    <div class="absolute bottom-3 left-3 right-3 text-white">
                        <h2 class="font-semibold">{{ item.title }}</h2>
                        <p v-if="item.territory" class="text-xs text-white/80">{{ item.territory }}</p>
                    </div>
                </div>
                <div class="p-4">
                    <p v-if="item.excerpt" class="text-sm text-pg-muted">{{ item.excerpt }}</p>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs font-medium text-pg-primary">
                        <span>{{ item.stop_count }} tappe</span>
                        <span v-if="item.duration">{{ item.duration }}</span>
                        <span v-if="item.difficulty">{{ difficultyLabel[item.difficulty] ?? item.difficulty }}</span>
                    </div>
                </div>
            </Link>

            <p v-if="!itineraries?.length" class="py-8 text-center text-sm text-pg-muted">
                Nessun itinerario disponibile al momento.
            </p>
        </main>
    </AppShell>
</template>
