<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    category: Object,
});

const isEdit = !!props.category;

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    icon: props.category?.icon ?? '',
    color: props.category?.color ?? '#2E7D32',
    description: props.category?.description ?? '',
    is_active: props.category?.is_active ?? true,
    is_indexable: props.category?.is_indexable ?? false,
    sort_order: props.category?.sort_order ?? 0,
});

function submit() {
    if (isEdit) {
        form.put(route('admin.categories.update', props.category.id));
    } else {
        form.post(route('admin.categories.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifica categoria' : 'Nuova categoria'" />

    <AdminShell>
        <div class="mb-6">
            <Link :href="route('admin.categories.index')" class="text-sm text-pg-primary">← Categorie</Link>
            <h1 class="mt-2 text-2xl font-bold text-pg-text">{{ isEdit ? 'Modifica categoria' : 'Nuova categoria' }}</h1>
            <p v-if="category?.pois_count" class="mt-1 text-sm text-pg-muted">
                Usata da {{ category.pois_count }} luoghi. Si disattiva, non si elimina.
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
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Icona</label>
                    <input v-model="form.icon" type="text" class="pg-input" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Colore</label>
                    <input v-model="form.color" type="text" class="pg-input" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Descrizione</label>
                <textarea v-model="form.description" rows="4" class="pg-input" />
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
                <input v-model="form.is_indexable" type="checkbox" class="rounded text-pg-primary" />
                Indicizzabile in futuro
            </label>
            <button type="submit" class="pg-btn-primary" :disabled="form.processing">Salva</button>
        </form>
    </AdminShell>
</template>
