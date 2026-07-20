<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    event: Object,
    pois: Array,
    statuses: Array,
});

const isEdit = !!props.event;

function formatForInput(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const form = useForm({
    title: props.event?.title ?? '',
    description: props.event?.description ?? '',
    starts_at: formatForInput(props.event?.starts_at),
    ends_at: formatForInput(props.event?.ends_at),
    poi_id: props.event?.poi_id ?? '',
    external_url: props.event?.external_url ?? '',
    is_featured: props.event?.is_featured ?? false,
    status: props.event?.status ?? 'draft',
});

function submit() {
    if (isEdit) {
        form.put(route('admin.events.update', props.event.id));
    } else {
        form.post(route('admin.events.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica evento' : 'Nuovo evento'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.events.index')" class="text-sm text-pg-primary">← Eventi</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica evento' : 'Nuovo evento' }}</h1>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Titolo *</label>
                <input v-model="form.title" type="text" class="pg-input" required />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Descrizione</label>
                <textarea v-model="form.description" rows="4" class="pg-input" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Inizio *</label>
                    <input v-model="form.starts_at" type="datetime-local" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Fine</label>
                    <input v-model="form.ends_at" type="datetime-local" class="pg-input" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">POI collegato</label>
                <select v-model="form.poi_id" class="pg-input">
                    <option value="">Nessuno</option>
                    <option v-for="poi in pois" :key="poi.id" :value="poi.id">{{ poi.name }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">URL esterno</label>
                <input v-model="form.external_url" type="url" class="pg-input" placeholder="https://..." />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Stato *</label>
                    <select v-model="form.status" class="pg-input" required>
                        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 self-end pb-2">
                    <input v-model="form.is_featured" type="checkbox" class="rounded text-pg-primary" />
                    <span class="text-sm font-medium">In evidenza in home</span>
                </label>
            </div>
            <button type="submit" class="pg-btn-primary w-full" :disabled="form.processing">Salva</button>
        </form>
    </AdminShell>
</template>
