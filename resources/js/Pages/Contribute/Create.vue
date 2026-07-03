<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    categories: Array,
});

const tab = ref('add');

const form = useForm({
    name: '',
    category_id: '',
    description: '',
    latitude: 43.1107,
    longitude: 12.3908,
    notes: '',
    type: 'new_poi',
});

function useMyLocation() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition((pos) => {
        form.latitude = pos.coords.latitude;
        form.longitude = pos.coords.longitude;
    });
}

function submit() {
    form.type = tab.value === 'report' ? 'report' : 'new_poi';
    form.post(route('contribute.store'));
}
</script>

<template>
    <Head title="Aggiungi luogo" />

    <AppShell active-nav="explore">
        <header class="flex items-center gap-3 bg-pg-surface px-4 py-4 shadow-sm">
            <Link :href="route('home')" class="rounded-full p-1">
                <PgIcon name="back" class="h-6 w-6" />
            </Link>
            <h1 class="text-lg font-semibold">Aggiungi un luogo</h1>
        </header>

        <div class="mx-4 mt-4 flex rounded-xl bg-gray-100 p-1">
            <button
                type="button"
                class="flex-1 rounded-lg py-2 text-sm font-medium transition"
                :class="tab === 'add' ? 'bg-pg-surface text-pg-primary shadow-sm' : 'text-pg-muted'"
                @click="tab = 'add'"
            >
                Aggiungi
            </button>
            <button
                type="button"
                class="flex-1 rounded-lg py-2 text-sm font-medium transition"
                :class="tab === 'report' ? 'bg-pg-surface text-pg-primary shadow-sm' : 'text-pg-muted'"
                @click="tab = 'report'"
            >
                Segnala problema
            </button>
        </div>

        <form class="space-y-4 px-4 py-6" @submit.prevent="submit">
            <div
                class="flex h-36 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 bg-pg-surface text-pg-muted"
            >
                <PgIcon name="camera-add" class="mb-2 h-8 w-8" />
                <span class="text-sm">Aggiungi foto</span>
                <span class="text-xs">(prossimamente)</span>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Nome luogo</label>
                <input v-model="form.name" type="text" class="pg-input" required />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Categoria</label>
                <select v-model="form.category_id" class="pg-input" required>
                    <option value="" disabled>Seleziona...</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Descrizione</label>
                <textarea v-model="form.description" rows="3" class="pg-input" />
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label class="text-sm font-medium">Posizione</label>
                    <button type="button" class="text-xs font-medium text-pg-primary" @click="useMyLocation">
                        La mia posizione
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="form.latitude" type="number" step="any" class="pg-input" placeholder="Latitudine" required />
                    <input v-model="form.longitude" type="number" step="any" class="pg-input" placeholder="Longitudine" required />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Informazioni aggiuntive</label>
                <textarea v-model="form.notes" rows="2" class="pg-input" placeholder="Orari, accessibilità..." />
            </div>

            <button
                type="submit"
                class="pg-btn-primary w-full py-4"
                :disabled="form.processing"
            >
                Invia proposta
            </button>
        </form>
    </AppShell>
</template>
