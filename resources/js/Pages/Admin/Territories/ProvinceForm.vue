<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    province: Object,
    regions: Array,
});

const isEdit = !!props.province;

const form = useForm({
    region_id: props.province?.region_id ?? '',
    name: props.province?.name ?? '',
    slug: props.province?.slug ?? '',
    code: props.province?.code ?? '',
    istat_code: props.province?.istat_code ?? '',
    latitude: props.province?.latitude ?? '',
    longitude: props.province?.longitude ?? '',
});

function submit() {
    if (isEdit) {
        form.put(route('admin.territories.provinces.update', props.province.id));
    } else {
        form.post(route('admin.territories.provinces.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica provincia' : 'Nuova provincia'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.territories.index', { tab: 'provinces' })" class="text-sm text-pg-primary">← Territori</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica provincia' : 'Nuova provincia' }}</h1>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Regione *</label>
                <select v-model="form.region_id" class="pg-input" required>
                    <option value="" disabled>Seleziona</option>
                    <option v-for="region in regions" :key="region.id" :value="region.id">{{ region.name }}</option>
                </select>
                <InputError :message="form.errors.region_id" />
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
                    <InputError :message="form.errors.slug" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Sigla</label>
                    <input v-model="form.code" type="text" class="pg-input" placeholder="PG" maxlength="8" />
                    <InputError :message="form.errors.code" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Codice ISTAT</label>
                <input v-model="form.istat_code" type="text" class="pg-input" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Latitudine</label>
                    <input v-model="form.latitude" type="number" step="any" class="pg-input" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Longitudine</label>
                    <input v-model="form.longitude" type="number" step="any" class="pg-input" />
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="pg-btn-primary flex-1" :disabled="form.processing">Salva</button>
                <Link :href="route('admin.territories.index', { tab: 'provinces' })" class="pg-btn-outline">Annulla</Link>
            </div>
        </form>
    </AdminShell>
</template>
