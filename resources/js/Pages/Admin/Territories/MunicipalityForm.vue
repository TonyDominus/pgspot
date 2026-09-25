<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    municipality: Object,
    provinces: Array,
});

const isEdit = !!props.municipality;

const form = useForm({
    province_id: props.municipality?.province_id ?? '',
    name: props.municipality?.name ?? '',
    slug: props.municipality?.slug ?? '',
    istat_code: props.municipality?.istat_code ?? '',
    latitude: props.municipality?.latitude ?? '',
    longitude: props.municipality?.longitude ?? '',
    intro: props.municipality?.intro ?? '',
    is_indexable: props.municipality?.is_indexable ?? false,
});

function submit() {
    if (isEdit) {
        form.put(route('admin.territories.municipalities.update', props.municipality.id));
    } else {
        form.post(route('admin.territories.municipalities.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica comune' : 'Nuovo comune'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.territories.index', { tab: 'municipalities' })" class="text-sm text-pg-primary">← Territori</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica comune' : 'Nuovo comune' }}</h1>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Provincia *</label>
                <select v-model="form.province_id" class="pg-input" required>
                    <option value="" disabled>Seleziona</option>
                    <option v-for="province in provinces" :key="province.id" :value="province.id">
                        {{ province.region?.name }} — {{ province.name }}
                    </option>
                </select>
                <InputError :message="form.errors.province_id" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nome *</label>
                <input v-model="form.name" type="text" class="pg-input" required />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Slug</label>
                    <input v-model="form.slug" type="text" class="pg-input" />
                    <p class="mt-1 text-xs text-pg-muted">Univoco nella provincia, non in tutta Italia.</p>
                    <InputError :message="form.errors.slug" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Codice ISTAT</label>
                    <input v-model="form.istat_code" type="text" class="pg-input" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Latitudine</label>
                    <input v-model="form.latitude" type="number" step="any" class="pg-input" />
                    <InputError :message="form.errors.latitude" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Longitudine</label>
                    <input v-model="form.longitude" type="number" step="any" class="pg-input" />
                    <InputError :message="form.errors.longitude" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Introduzione</label>
                <textarea v-model="form.intro" rows="4" class="pg-input" />
                <InputError :message="form.errors.intro" />
            </div>
            <label class="flex items-center gap-3 text-sm">
                <input v-model="form.is_indexable" type="checkbox" class="rounded border-gray-300 text-pg-primary" />
                <span>Indicizzabile in futuro (nessuna pagina pubblica viene creata ora)</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="pg-btn-primary flex-1" :disabled="form.processing">Salva</button>
                <Link :href="route('admin.territories.index', { tab: 'municipalities' })" class="pg-btn-outline">Annulla</Link>
            </div>
        </form>
    </AdminShell>
</template>
