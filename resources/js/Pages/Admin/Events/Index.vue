<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    events: Object,
});

function destroyEvent(id) {
    if (!confirm('Eliminare questo evento?')) return;
    router.delete(route('admin.events.destroy', id));
}
</script>

<template>
    <Head title="Eventi" />

    <AdminShell>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-pg-text">Eventi</h1>
                <p class="text-sm text-pg-muted">Gestisci eventi e vetrine in home</p>
            </div>
            <Link :href="route('admin.events.create')" class="pg-btn-primary">Nuovo evento</Link>
        </div>

        <div class="pg-card overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-gray-100 text-xs uppercase text-pg-muted">
                    <tr>
                        <th class="px-4 py-3">Titolo</th>
                        <th class="px-4 py-3">Inizio</th>
                        <th class="px-4 py-3">Stato</th>
                        <th class="px-4 py-3">In evidenza</th>
                        <th class="px-4 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="event in events.data" :key="event.id" class="border-b border-gray-50">
                        <td class="px-4 py-3 font-medium">{{ event.title }}</td>
                        <td class="px-4 py-3 text-pg-muted">{{ new Date(event.starts_at).toLocaleString('it-IT') }}</td>
                        <td class="px-4 py-3 capitalize">{{ event.status }}</td>
                        <td class="px-4 py-3">{{ event.is_featured ? 'Sì' : 'No' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.events.edit', event.id)" class="mr-3 text-pg-primary hover:underline">Modifica</Link>
                            <button type="button" class="text-pg-error hover:underline" @click="destroyEvent(event.id)">Elimina</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminShell>
</template>
