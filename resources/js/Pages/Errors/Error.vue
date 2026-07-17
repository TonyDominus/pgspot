<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineProps({
    status: { type: Number, default: 404 },
});

const titles = {
    403: 'Accesso negato',
    404: 'Pagina non trovata',
    500: 'Errore del server',
    503: 'Manutenzione',
};

const messages = {
    403: 'Non hai i permessi per visualizzare questa pagina.',
    404: 'La pagina che cerchi non esiste o è stata spostata.',
    500: 'Qualcosa è andato storto. Riprova tra poco.',
    503: 'Stiamo effettuando manutenzione. Torna presto.',
};
</script>

<template>
    <Head :title="titles[status] ?? 'Errore'" />

    <AppShell hide-footer>
        <div class="flex min-h-[60vh] flex-col items-center justify-center px-6 py-16 text-center">
            <p class="text-6xl font-bold text-pg-primary/30">{{ status }}</p>
            <h1 class="mt-4 text-xl font-bold text-pg-text">{{ titles[status] ?? 'Errore' }}</h1>
            <p class="mt-2 max-w-md text-sm text-pg-muted">{{ messages[status] ?? 'Si è verificato un errore.' }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <Link :href="route('home')" class="pg-btn-primary">Torna alla mappa</Link>
                <Link :href="route('poi.index')" class="pg-btn-outline">Lista luoghi</Link>
            </div>
        </div>
    </AppShell>
</template>
