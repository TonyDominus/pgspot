<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    poi: Object,
    categories: Array,
    statuses: Array,
});

const form = useForm({
    name: props.poi.name,
    description: props.poi.description ?? '',
    latitude: props.poi.latitude,
    longitude: props.poi.longitude,
    address: props.poi.address ?? '',
    status: props.poi.status,
    category_ids: props.poi.categories?.map((c) => c.id) ?? [],
});

function submit() {
    form.put(route('admin.pois.update', props.poi.id));
}
</script>

<template>
    <Head :title="`Modifica ${poi.name}`" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.pois.index')" class="text-sm text-pg-primary">← Torna alla lista</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">Modifica POI</h1>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Nome *</label>
                <input v-model="form.name" type="text" class="pg-input" required />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Descrizione</label>
                <textarea v-model="form.description" rows="4" class="pg-input" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Latitudine *</label>
                    <input v-model="form.latitude" type="number" step="any" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Longitudine *</label>
                    <input v-model="form.longitude" type="number" step="any" class="pg-input" required />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Indirizzo</label>
                <input v-model="form.address" type="text" class="pg-input" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Stato *</label>
                <select v-model="form.status" class="pg-input" required>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Categorie</label>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="cat in categories"
                        :key="cat.id"
                        class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm"
                        :class="form.category_ids.includes(cat.id) ? 'border-pg-primary bg-pg-primary/5' : 'border-gray-200'"
                    >
                        <input v-model="form.category_ids" type="checkbox" :value="cat.id" class="rounded text-pg-primary" />
                        {{ cat.name }}
                    </label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="pg-btn-primary flex-1" :disabled="form.processing">Salva</button>
                <Link :href="route('admin.pois.index')" class="pg-btn-outline">Annulla</Link>
            </div>
        </form>
    </AdminShell>
</template>
