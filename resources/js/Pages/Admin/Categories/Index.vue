<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    categories: Array,
});
</script>

<template>
    <Head title="Categorie" />

    <AdminShell>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-pg-text">Categorie</h1>
            <Link :href="route('admin.categories.create')" class="pg-btn-primary">Nuova categoria</Link>
        </div>

        <div class="pg-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-pg-muted">
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">POI</th>
                        <th class="px-4 py-3">Stato</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="category in categories" :key="category.id" class="border-b last:border-0">
                        <td class="px-4 py-3 font-medium">{{ category.name }}</td>
                        <td class="px-4 py-3 text-pg-muted">{{ category.slug }}</td>
                        <td class="px-4 py-3">{{ category.pois_count }}</td>
                        <td class="px-4 py-3">{{ category.is_active ? 'Attiva' : 'Disattivata' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.categories.edit', category.id)" class="text-pg-primary">Modifica</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminShell>
</template>
