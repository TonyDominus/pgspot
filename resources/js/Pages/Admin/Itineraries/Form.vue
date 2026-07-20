<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    itinerary: Object,
    pois: Array,
});

const isEdit = !!props.itinerary;

const form = useForm({
    title: props.itinerary?.title ?? '',
    description: props.itinerary?.description ?? '',
    duration: props.itinerary?.duration ?? '',
    poi_ids: props.itinerary?.poi_ids ?? [],
    is_published: props.itinerary?.is_published ?? true,
    sort_order: props.itinerary?.sort_order ?? 0,
});

function togglePoi(id) {
    const idx = form.poi_ids.indexOf(id);
    if (idx === -1) {
        form.poi_ids.push(id);
    } else {
        form.poi_ids.splice(idx, 1);
    }
}

function submit() {
    if (isEdit) {
        form.put(route('admin.itineraries.update', props.itinerary.id));
    } else {
        form.post(route('admin.itineraries.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica itinerario' : 'Nuovo itinerario'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.itineraries.index')" class="text-sm text-pg-primary">← Itinerari</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica itinerario' : 'Nuovo itinerario' }}</h1>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Titolo *</label>
                <input v-model="form.title" type="text" class="pg-input" required />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Descrizione</label>
                <textarea v-model="form.description" rows="3" class="pg-input" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Durata</label>
                    <input v-model="form.duration" type="text" class="pg-input" placeholder="es. 2h" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Ordine</label>
                    <input v-model="form.sort_order" type="number" min="0" class="pg-input" />
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Tappe (ordine di selezione)</label>
                <div class="max-h-64 space-y-2 overflow-y-auto rounded-xl border border-gray-100 p-3">
                    <label
                        v-for="poi in pois"
                        :key="poi.id"
                        class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50"
                    >
                        <input
                            type="checkbox"
                            :checked="form.poi_ids.includes(poi.id)"
                            class="rounded text-pg-primary"
                            @change="togglePoi(poi.id)"
                        />
                        <span class="text-sm">{{ poi.name }}</span>
                        <span v-if="form.poi_ids.includes(poi.id)" class="ml-auto text-xs text-pg-primary">
                            #{{ form.poi_ids.indexOf(poi.id) + 1 }}
                        </span>
                    </label>
                </div>
            </div>

            <label class="flex items-center gap-2">
                <input v-model="form.is_published" type="checkbox" class="rounded text-pg-primary" />
                <span class="text-sm font-medium">Pubblicato</span>
            </label>

            <button type="submit" class="pg-btn-primary w-full" :disabled="form.processing">Salva</button>
        </form>
    </AdminShell>
</template>
