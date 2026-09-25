<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    itineraries: Object,
    filters: Object,
});

const localFilters = reactive({
    status: props.filters?.status ?? '',
    q: props.filters?.q ?? '',
});

const statusLabel = {
    draft: 'Bozza',
    published: 'Pubblicato',
    archived: 'Archiviato',
};

function applyFilters() {
    router.get(route('admin.itineraries.index'), localFilters, { preserveState: true });
}

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
                <p class="text-sm text-pg-muted">Percorsi editoriali con tappe ordinate</p>
            </div>
            <Link :href="route('admin.itineraries.create')" class="pg-btn-primary">Nuovo itinerario</Link>
        </div>

        <form class="mb-4 flex flex-wrap gap-2" @submit.prevent="applyFilters">
            <input v-model="localFilters.q" class="pg-input max-w-xs" placeholder="Cerca titolo" />
            <select v-model="localFilters.status" class="pg-input max-w-[12rem]">
                <option value="">Tutti gli stati</option>
                <option value="draft">Bozza</option>
                <option value="published">Pubblicato</option>
                <option value="archived">Archiviato</option>
            </select>
            <button type="submit" class="pg-btn-outline text-sm">Filtra</button>
        </form>

        <div class="space-y-3">
            <article v-for="item in itineraries.data" :key="item.id" class="pg-card flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <h2 class="font-semibold text-pg-text">{{ item.title }}</h2>
                    <p class="text-sm text-pg-muted">
                        {{ statusLabel[item.status] ?? item.status }}
                        · {{ item.stop_count ?? item.pois_count ?? 0 }} tappe
                        <span v-if="item.territory"> · {{ item.territory }}</span>
                    </p>
                    <p v-if="item.has_unpublished_stops" class="text-xs text-amber-700">Contiene tappe non pubblicate</p>
                    <p class="text-xs text-pg-muted">Aggiornato {{ new Date(item.updated_at).toLocaleString('it-IT') }}</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('admin.itineraries.edit', item.id)" class="text-pg-primary hover:underline">Modifica</Link>
                    <button type="button" class="text-pg-error hover:underline" @click="destroyItinerary(item.id)">Elimina</button>
                </div>
            </article>
            <p v-if="!itineraries.data?.length" class="py-8 text-center text-sm text-pg-muted">Nessun itinerario.</p>
        </div>
    </AdminShell>
</template>
