<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    region: Object,
});

const isEdit = !!props.region;

const form = useForm({
    name: props.region?.name ?? '',
    slug: props.region?.slug ?? '',
    istat_code: props.region?.istat_code ?? '',
});

function submit() {
    if (isEdit) {
        form.put(route('admin.territories.regions.update', props.region.id));
    } else {
        form.post(route('admin.territories.regions.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica regione' : 'Nuova regione'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.territories.index', { tab: 'regions' })" class="text-sm text-pg-primary">← Territori</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica regione' : 'Nuova regione' }}</h1>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Nome *</label>
                <input v-model="form.name" type="text" class="pg-input" required />
                <InputError :message="form.errors.name" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Slug</label>
                <input v-model="form.slug" type="text" class="pg-input" placeholder="Generato dal nome se vuoto" />
                <InputError :message="form.errors.slug" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Codice ISTAT</label>
                <input v-model="form.istat_code" type="text" class="pg-input" placeholder="Solo se noto, non inventarlo" />
                <InputError :message="form.errors.istat_code" />
            </div>
            <div class="flex gap-3">
                <button type="submit" class="pg-btn-primary flex-1" :disabled="form.processing">Salva</button>
                <Link :href="route('admin.territories.index', { tab: 'regions' })" class="pg-btn-outline">Annulla</Link>
            </div>
        </form>
    </AdminShell>
</template>
