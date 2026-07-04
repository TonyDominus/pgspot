<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PgIcon from '@/Components/Icons/PgIcon.vue';

const props = defineProps({
    pois: Object,
    filters: Object,
    statuses: Array,
});

function sortLink(column) {
    const dir = props.filters.sort === column && props.filters.dir !== 'desc' ? 'desc' : 'asc';
    router.get(route('admin.pois.index'), { ...props.filters, sort: column, dir }, { preserveState: true });
}

function destroyPoi(id) {
    if (!confirm('Eliminare questo POI?')) return;
    router.delete(route('admin.pois.destroy', id));
}

const sortIcon = (col) => {
    if (props.filters.sort !== col) return '↕';
    return props.filters.dir === 'desc' ? '↓' : '↑';
};
</script>

<template>
    <Head title="Gestione POI" />

    <AdminShell>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-pg-text">POI</h1>
                <p class="text-sm text-pg-muted">Gestisci e modifica i punti di interesse</p>
            </div>
            <form class="flex gap-2" @submit.prevent="router.get(route('admin.pois.index'), { ...filters, q: $event.target.q.value })">
                <input name="q" :value="filters.q" type="search" placeholder="Cerca..." class="pg-input text-sm" />
                <select
                    :value="filters.status"
                    class="pg-input text-sm"
                    @change="router.get(route('admin.pois.index'), { ...filters, status: $event.target.value || undefined })"
                >
                    <option value="">Tutti gli stati</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </form>
        </div>

        <div class="pg-card overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-gray-100 text-xs uppercase text-pg-muted">
                    <tr>
                        <th class="cursor-pointer px-4 py-3" @click="sortLink('name')">Nome {{ sortIcon('name') }}</th>
                        <th class="px-4 py-3">Categorie</th>
                        <th class="cursor-pointer px-4 py-3" @click="sortLink('status')">Stato {{ sortIcon('status') }}</th>
                        <th class="cursor-pointer px-4 py-3" @click="sortLink('rating')">Rating {{ sortIcon('rating') }}</th>
                        <th class="cursor-pointer px-4 py-3" @click="sortLink('updated_at')">Aggiornato {{ sortIcon('updated_at') }}</th>
                        <th class="px-4 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="poi in pois.data" :key="poi.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                        <td class="px-4 py-3 font-medium text-pg-text">{{ poi.name }}</td>
                        <td class="px-4 py-3">
                            <span
                                v-for="cat in poi.categories"
                                :key="cat.id"
                                class="mr-1 inline-block rounded-full px-2 py-0.5 text-[10px] text-white"
                                :style="{ backgroundColor: cat.color }"
                            >
                                {{ cat.name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ poi.status }}</td>
                        <td class="px-4 py-3">{{ poi.rating ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-pg-muted">{{ new Date(poi.updated_at).toLocaleDateString('it-IT') }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.pois.edit', poi.id)" class="mr-3 text-pg-primary hover:underline">Modifica</Link>
                            <button type="button" class="text-pg-error hover:underline" @click="destroyPoi(poi.id)">Elimina</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pois.links?.length > 3" class="mt-4 flex flex-wrap justify-center gap-2">
            <Link
                v-for="link in pois.links"
                :key="link.label"
                :href="link.url"
                class="rounded-lg px-3 py-1 text-sm"
                :class="link.active ? 'bg-pg-primary text-white' : 'bg-gray-100 text-pg-muted'"
                v-html="link.label"
            />
        </div>
    </AdminShell>
</template>
