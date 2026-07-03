<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    routes: Array,
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
            <article
                v-for="(item, i) in routes"
                :key="i"
                class="pg-card overflow-hidden"
            >
                <div class="h-28 bg-gradient-to-br from-pg-primary to-pg-primary-dark p-4 text-white">
                    <PgIcon name="route" class="mb-2 h-6 w-6 opacity-80" />
                    <h2 class="font-semibold">{{ item.title }}</h2>
                </div>
                <div class="p-4">
                    <p class="text-sm text-pg-muted">{{ item.description }}</p>
                    <div class="mt-3 flex gap-4 text-xs font-medium text-pg-primary">
                        <span>{{ item.stops }} tappe</span>
                        <span>{{ item.duration }}</span>
                    </div>
                </div>
            </article>

            <p class="py-4 text-center text-sm text-pg-muted">Altri itinerari in arrivo.</p>
        </main>
    </AppShell>
</template>
