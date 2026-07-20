<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    itineraries: Object,
});

function destroyItinerary(id) {
    if (!confirm('Eliminare questo itinerario?')) return;
    router.delete(route('admin.itineraries.destroy', id));
}
</script>

<template>
    <Head title="Itinerari" />

    <AdminShell>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-pg-text">Itinerari</h1>
                <p class="text-sm text-pg-muted">Percorsi consigliati con tappe POI</p>
            </div>
            <Link :href="route('admin.itineraries.create')" class="pg-btn-primary">Nuovo itinerario</Link>
        </div>

        <div class="space-y-3">
            <article v-for="item in itineraries.data" :key="item.id" class="pg-card flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <h2 class="font-semibold text-pg-text">{{ item.title }}</h2>
                    <p class="text-sm text-pg-muted">{{ item.duration }} · {{ item.poi_ids?.length ?? 0 }} tappe</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('admin.itineraries.edit', item.id)" class="text-pg-primary hover:underline">Modifica</Link>
                    <button type="button" class="text-pg-error hover:underline" @click="destroyItinerary(item.id)">Elimina</button>
                </div>
            </article>
        </div>
    </AdminShell>
</template>
