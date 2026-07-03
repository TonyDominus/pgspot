<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    sponsorship: Object,
    pois: Array,
    types: Array,
    placements: Array,
});

const isEdit = !!props.sponsorship;

function formatForInput(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const form = useForm({
    title: props.sponsorship?.title ?? '',
    description: props.sponsorship?.description ?? '',
    type: props.sponsorship?.type ?? 'card',
    partner_name: props.sponsorship?.partner_name ?? '',
    amount_paid: props.sponsorship?.amount_paid ?? 0,
    starts_at: formatForInput(props.sponsorship?.starts_at),
    ends_at: formatForInput(props.sponsorship?.ends_at),
    is_active: props.sponsorship?.is_active ?? true,
    poi_id: props.sponsorship?.poi_id ?? '',
    external_url: props.sponsorship?.external_url ?? '',
    placement: props.sponsorship?.placement ?? 'home_sheet',
    notes: props.sponsorship?.notes ?? '',
});

function submit() {
    if (isEdit) {
        form.put(route('admin.sponsorships.update', props.sponsorship.id));
    } else {
        form.post(route('admin.sponsorships.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica sponsor' : 'Nuova sponsorizzazione'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.sponsorships.index')" class="text-sm text-pg-primary">← Torna alla lista</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">
                {{ isEdit ? 'Modifica sponsorizzazione' : 'Nuova sponsorizzazione' }}
            </h1>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Titolo campagna *</label>
                    <input v-model="form.title" type="text" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Partner / Brand *</label>
                    <input v-model="form.partner_name" type="text" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Importo pagato (€) *</label>
                    <input v-model="form.amount_paid" type="number" step="0.01" min="0" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Tipo *</label>
                    <select v-model="form.type" class="pg-input" required>
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Posizionamento *</label>
                    <select v-model="form.placement" class="pg-input" required>
                        <option v-for="p in placements" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Inizio *</label>
                    <input v-model="form.starts_at" type="datetime-local" class="pg-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Fine *</label>
                    <input v-model="form.ends_at" type="datetime-local" class="pg-input" required />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">POI collegato (opzionale)</label>
                    <select v-model="form.poi_id" class="pg-input">
                        <option value="">Nessun POI</option>
                        <option v-for="poi in pois" :key="poi.id" :value="poi.id">{{ poi.name }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">URL esterno (se no POI)</label>
                    <input v-model="form.external_url" type="url" class="pg-input" placeholder="https://..." />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Descrizione</label>
                    <textarea v-model="form.description" rows="3" class="pg-input" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Note interne</label>
                    <textarea v-model="form.notes" rows="2" class="pg-input" placeholder="Note admin, fattura, contatto..." />
                </div>
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-pg-primary focus:ring-pg-primary" />
                        <span class="text-sm font-medium">Campagna attiva</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="pg-btn-primary flex-1" :disabled="form.processing">
                    {{ isEdit ? 'Salva modifiche' : 'Crea sponsorizzazione' }}
                </button>
                <Link :href="route('admin.sponsorships.index')" class="pg-btn-outline">Annulla</Link>
            </div>
        </form>
    </AdminShell>
</template>
