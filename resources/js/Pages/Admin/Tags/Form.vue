<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tag: Object,
});

const isEdit = !!props.tag;

const form = useForm({
    name: props.tag?.name ?? '',
    slug: props.tag?.slug ?? '',
    is_active: props.tag?.is_active ?? true,
    is_filterable: props.tag?.is_filterable ?? true,
    is_indexable: props.tag?.is_indexable ?? false,
    sort_order: props.tag?.sort_order ?? 0,
});

function submit() {
    if (isEdit) {
        form.put(route('admin.tags.update', props.tag.id));
    } else {
        form.post(route('admin.tags.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica caratteristica' : 'Nuova caratteristica'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.tags.index')" class="text-sm text-pg-primary">← Caratteristiche</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica caratteristica' : 'Nuova caratteristica' }}</h1>
            <p v-if="tag?.pois_count" class="mt-1 text-sm text-pg-muted">
                Usata da {{ tag.pois_count }} luoghi. Si disattiva, non si elimina.
            </p>
        </div>

        <form class="pg-card mx-auto max-w-2xl space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Nome *</label>
                <input v-model="form.name" type="text" class="pg-input" required />
                <InputError :message="form.errors.name" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Slug</label>
                <input v-model="form.slug" type="text" class="pg-input" />
                <InputError :message="form.errors.slug" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Ordine</label>
                <input v-model="form.sort_order" type="number" min="0" class="pg-input" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.is_active" type="checkbox" class="rounded text-pg-primary" />
                Attiva
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.is_filterable" type="checkbox" class="rounded text-pg-primary" />
                Usabile come filtro
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.is_indexable" type="checkbox" class="rounded text-pg-primary" />
                Indicizzabile in futuro
            </label>
            <button type="submit" class="pg-btn-primary" :disabled="form.processing">Salva</button>
        </form>
    </AdminShell>
</template>
