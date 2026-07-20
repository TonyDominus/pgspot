<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    events: Array,
});

function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleString('it-IT', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Eventi" />

    <AppShell active-nav="events">
        <header class="bg-pg-surface px-4 py-5 shadow-sm">
            <h1 class="text-xl font-bold text-pg-text">Eventi</h1>
            <p class="text-sm text-pg-muted">Cosa succede a Perugia</p>
        </header>

        <main class="space-y-4 px-4 py-4">
            <article
                v-for="event in events"
                :key="event.id"
                class="pg-card overflow-hidden"
            >
                <div class="bg-gradient-to-br from-pg-primary to-pg-primary-dark p-4 text-white">
                    <PgIcon name="bell" class="mb-2 h-6 w-6 opacity-80" />
                    <h2 class="text-lg font-semibold">{{ event.title }}</h2>
                    <p class="mt-1 text-sm opacity-90">{{ formatDate(event.starts_at) }}</p>
                </div>
                <div class="p-4">
                    <p v-if="event.description" class="text-sm text-pg-muted">{{ event.description }}</p>
                    <a
                        v-if="event.external_url"
                        :href="event.external_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-block text-sm font-medium text-pg-primary underline"
                    >
                        Maggiori informazioni
                    </a>
                </div>
            </article>

            <p v-if="!events?.length" class="py-8 text-center text-sm text-pg-muted">
                Nessun evento in programma al momento.
            </p>
        </main>
    </AppShell>
</template>
