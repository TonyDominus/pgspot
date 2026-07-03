<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    sponsorships: Array,
});

function formatDate(d) {
    return new Date(d).toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' });
}

function statusLabel(s) {
    const now = new Date();
    const start = new Date(s.starts_at);
    const end = new Date(s.ends_at);
    if (!s.is_active) return { text: 'Disattivato', class: 'bg-gray-100 text-gray-600' };
    if (now < start) return { text: 'Programmato', class: 'bg-blue-100 text-blue-700' };
    if (now > end) return { text: 'Scaduto', class: 'bg-red-100 text-red-700' };
    return { text: 'Attivo', class: 'bg-green-100 text-green-700' };
}

function destroy(id) {
    if (confirm('Eliminare questa sponsorizzazione?')) {
        router.delete(route('admin.sponsorships.destroy', id));
    }
}
</script>

<template>
    <Head title="Sponsorizzazioni" />

    <AdminShell>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-pg-text">Sponsorizzazioni</h1>
                <p class="text-sm text-pg-muted">Gestisci contenuti sponsorizzati e incassi</p>
            </div>
            <Link :href="route('admin.sponsorships.create')" class="pg-btn-primary gap-2">
                <PgIcon name="plus" class="h-4 w-4" />
                Nuova campagna
            </Link>
        </div>

        <div v-if="sponsorships.length === 0" class="pg-card py-16 text-center text-pg-muted">
            Nessuna sponsorizzazione. Creane una per iniziare.
        </div>

        <div class="space-y-3">
            <article
                v-for="s in sponsorships"
                :key="s.id"
                class="pg-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold text-pg-text">{{ s.title }}</h2>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusLabel(s).class">
                            {{ statusLabel(s).text }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-pg-muted">{{ s.partner_name }} · {{ s.type }}</p>
                    <p class="mt-1 text-xs text-pg-muted">
                        {{ formatDate(s.starts_at) }} → {{ formatDate(s.ends_at) }}
                        <span v-if="s.poi"> · POI: {{ s.poi.name }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <p class="text-lg font-bold text-amber-700">€ {{ Number(s.amount_paid).toFixed(2) }}</p>
                    <div class="flex gap-2">
                        <Link :href="route('admin.sponsorships.edit', s.id)" class="pg-btn-outline px-3 py-2 text-sm">
                            Modifica
                        </Link>
                        <button type="button" class="rounded-xl px-3 py-2 text-sm text-pg-error hover:bg-red-50" @click="destroy(s.id)">
                            Elimina
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </AdminShell>
</template>
