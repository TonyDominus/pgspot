<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    itinerary: Object,
    stops: Array,
    statuses: Array,
    cover_url: String,
    has_unpublished_stops: Boolean,
});

const isEdit = !!props.itinerary;
const query = ref('');
const results = ref([]);
const searching = ref(false);

const form = useForm({
    title: props.itinerary?.title ?? '',
    slug: props.itinerary?.slug ?? '',
    excerpt: props.itinerary?.excerpt ?? '',
    description: props.itinerary?.description ?? '',
    estimated_duration_minutes: props.itinerary?.estimated_duration_minutes ?? '',
    estimated_distance_km: props.itinerary?.estimated_distance_km ?? '',
    difficulty: props.itinerary?.difficulty ?? '',
    status: props.itinerary?.status ?? 'draft',
    sort_order: props.itinerary?.sort_order ?? 0,
    cover: null,
    stops: (props.stops ?? []).map((stop) => ({
        poi_id: stop.id,
        name: stop.name,
        municipality: stop.municipality,
        primary_category: stop.primary_category,
        status: stop.status,
        unpublished: stop.unpublished,
        note: stop.note ?? '',
    })),
});

let searchTimer = null;

function searchPois() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        if (query.value.trim().length < 2) {
            results.value = [];
            return;
        }
        searching.value = true;
        try {
            const response = await fetch(`${route('admin.itineraries.pois')}?q=${encodeURIComponent(query.value.trim())}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            results.value = data.pois ?? [];
        } finally {
            searching.value = false;
        }
    }, 250);
}

function addStop(poi) {
    if (form.stops.some((stop) => stop.poi_id === poi.id)) return;
    form.stops.push({
        poi_id: poi.id,
        name: poi.name,
        municipality: poi.municipality,
        primary_category: poi.primary_category,
        status: poi.status,
        unpublished: poi.status !== 'published',
        note: '',
    });
    results.value = [];
    query.value = '';
}

function removeStop(index) {
    form.stops.splice(index, 1);
}

function moveStop(index, direction) {
    const next = index + direction;
    if (next < 0 || next >= form.stops.length) return;
    const copy = [...form.stops];
    const [row] = copy.splice(index, 1);
    copy.splice(next, 0, row);
    form.stops = copy;
}

function submit() {
    if (isEdit) {
        form.put(route('admin.itineraries.update', props.itinerary.id), { forceFormData: true });
        return;
    }
    form.post(route('admin.itineraries.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica itinerario' : 'Nuovo itinerario'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.itineraries.index')" class="text-sm text-pg-primary">← Itinerari</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica itinerario' : 'Nuovo itinerario' }}</h1>
        </div>

        <p v-if="has_unpublished_stops" class="mb-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
            Una o più tappe non sono pubblicate. L'itinerario pubblico nasconde quelle tappe; con meno di due tappe pubblicate non è visibile.
        </p>

        <form class="mx-auto max-w-3xl space-y-5" @submit.prevent="submit">
            <div class="pg-card space-y-4 p-6">
                <div>
                    <label class="mb-1 block text-sm font-medium">Titolo *</label>
                    <input v-model="form.title" type="text" class="pg-input" required />
                    <InputError :message="form.errors.title" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Slug</label>
                    <input v-model="form.slug" type="text" class="pg-input" placeholder="generato dal titolo se vuoto" />
                    <InputError :message="form.errors.slug" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Estratto</label>
                    <input v-model="form.excerpt" type="text" class="pg-input" maxlength="255" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Descrizione</label>
                    <textarea v-model="form.description" rows="4" class="pg-input" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Copertina</label>
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="pg-input" @change="form.cover = $event.target.files?.[0] ?? null" />
                    <img v-if="cover_url && !form.cover" :src="cover_url" alt="" class="mt-2 h-28 w-full rounded-xl object-cover" />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Durata (minuti)</label>
                        <input v-model="form.estimated_duration_minutes" type="number" min="1" class="pg-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Distanza (km)</label>
                        <input v-model="form.estimated_distance_km" type="number" min="0" step="0.1" class="pg-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Difficoltà</label>
                        <select v-model="form.difficulty" class="pg-input">
                            <option value="">Non indicata</option>
                            <option value="easy">Facile</option>
                            <option value="medium">Media</option>
                            <option value="hard">Impegnativa</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Stato</label>
                        <select v-model="form.status" class="pg-input">
                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Ordine lista</label>
                        <input v-model="form.sort_order" type="number" min="0" class="pg-input" />
                    </div>
                </div>
            </div>

            <div class="pg-card space-y-4 p-6">
                <h2 class="font-semibold">Tappe</h2>
                <input
                    v-model="query"
                    type="search"
                    class="pg-input"
                    placeholder="Cerca un luogo per nome, comune o categoria"
                    @input="searchPois"
                />
                <p v-if="searching" class="text-xs text-pg-muted">Ricerca...</p>
                <ul v-if="results.length" class="divide-y rounded-xl border border-gray-100">
                    <li v-for="poi in results" :key="poi.id">
                        <button type="button" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50" @click="addStop(poi)">
                            <span>
                                <span class="font-medium">{{ poi.name }}</span>
                                <span class="block text-xs text-pg-muted">
                                    {{ poi.municipality ?? 'Comune n/d' }}
                                    <span v-if="poi.primary_category"> · {{ poi.primary_category }}</span>
                                    <span v-if="poi.status !== 'published'"> · {{ poi.status }}</span>
                                </span>
                            </span>
                            <span class="text-pg-primary">Aggiungi</span>
                        </button>
                    </li>
                </ul>

                <ol class="space-y-3">
                    <li v-for="(stop, index) in form.stops" :key="stop.poi_id" class="rounded-xl border border-gray-100 p-3">
                        <div class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-pg-primary text-xs font-bold text-white">{{ index + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ stop.name }}</p>
                                <p class="text-xs text-pg-muted">
                                    {{ stop.municipality ?? 'Comune n/d' }}
                                    <span v-if="stop.primary_category"> · {{ stop.primary_category }}</span>
                                    <span v-if="stop.unpublished" class="text-amber-700"> · non pubblicato</span>
                                </p>
                                <input v-model="stop.note" class="pg-input mt-2 text-sm" placeholder="Nota opzionale per questa tappa" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <button type="button" class="text-xs text-pg-primary" @click="moveStop(index, -1)">Su</button>
                                <button type="button" class="text-xs text-pg-primary" @click="moveStop(index, 1)">Giù</button>
                                <button type="button" class="text-xs text-pg-error" @click="removeStop(index)">Rimuovi</button>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>

            <button type="submit" class="pg-btn-primary w-full" :disabled="form.processing">Salva</button>
        </form>
    </AdminShell>
</template>
