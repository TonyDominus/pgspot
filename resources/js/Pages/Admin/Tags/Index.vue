<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    tags: Array,
});
</script>

<template>
    <Head title="Caratteristiche" />

    <AdminShell>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-pg-text">Caratteristiche</h1>
                <p class="text-sm text-pg-muted">Il carattere del luogo, non il tipo di posto.</p>
            </div>
            <Link :href="route('admin.tags.create')" class="pg-btn-primary">Nuova caratteristica</Link>
        </div>

        <div class="pg-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-pg-muted">
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">POI</th>
                        <th class="px-4 py-3">Filtro</th>
                        <th class="px-4 py-3">Stato</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tag in tags" :key="tag.id" class="border-b last:border-0">
                        <td class="px-4 py-3 font-medium">{{ tag.name }}</td>
                        <td class="px-4 py-3 text-pg-muted">{{ tag.slug }}</td>
                        <td class="px-4 py-3">{{ tag.pois_count }}</td>
                        <td class="px-4 py-3">{{ tag.is_filterable ? 'Sì' : 'No' }}</td>
                        <td class="px-4 py-3">{{ tag.is_active ? 'Attiva' : 'Disattivata' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.tags.edit', tag.id)" class="text-pg-primary">Modifica</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminShell>
</template>
