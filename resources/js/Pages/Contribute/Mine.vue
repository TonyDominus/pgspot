<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';

defineProps({
    contributions: Array,
});

const success = computed(() => usePage().props.flash?.success);

const labels = {
    new_poi: 'Nuovo spot',
    edit: 'Modifica',
    photo: 'Foto',
    report: 'Segnalazione',
    pending: 'In revisione',
    approved: 'Approvato',
    rejected: 'Rifiutato',
};
</script>

<template>
    <Head title="I miei contributi" />

    <AppShell active-nav="profile">
        <header class="bg-pg-surface px-4 py-4 shadow-sm">
            <h1 class="text-lg font-semibold">I miei contributi</h1>
        </header>

        <p v-if="success" class="mx-4 mt-4 rounded-xl bg-green-50 p-3 text-sm">{{ success }}</p>

        <div class="space-y-3 px-4 py-4">
            <article v-for="item in contributions" :key="item.id" class="pg-card p-4">
                <p class="text-sm font-medium">{{ labels[item.type] ?? item.type }} · {{ item.spot ?? 'Luogo' }}</p>
                <p class="text-xs text-pg-muted">
                    {{ item.created_at ? new Date(item.created_at).toLocaleString('it-IT') : '' }}
                    · {{ labels[item.status] ?? item.status }}
                </p>
                <Link v-if="item.slug && item.status === 'approved'" :href="route('poi.show', item.slug)" class="mt-1 inline-block text-sm text-pg-primary">
                    Apri luogo
                </Link>
            </article>
            <p v-if="!contributions.length" class="text-sm text-pg-muted">Non hai ancora inviato contributi.</p>
            <Link :href="route('home')" class="pg-btn-outline mt-2 inline-flex">Torna alla mappa</Link>
        </div>
    </AppShell>
</template>
