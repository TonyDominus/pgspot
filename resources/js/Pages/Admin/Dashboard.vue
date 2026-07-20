<script setup>
import AdminShell from '@/Layouts/AdminShell.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    stats: Object,
});

const isSuperAdmin = computed(() => usePage().props.auth?.user?.role === 'superadmin');
</script>

<template>
    <Head title="Admin" />

    <AdminShell>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-pg-text">Dashboard</h1>
            <p class="text-sm text-pg-muted">Panoramica della piattaforma</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="pg-card p-5">
                <p class="text-sm text-pg-muted">Utenti</p>
                <p class="mt-1 text-3xl font-bold text-pg-text">{{ stats.users }}</p>
            </div>
            <div class="pg-card p-5">
                <p class="text-sm text-pg-muted">POI pubblicati</p>
                <p class="mt-1 text-3xl font-bold text-pg-primary">{{ stats.pois_published }}</p>
            </div>
            <div class="pg-card p-5">
                <p class="text-sm text-pg-muted">In attesa</p>
                <p class="mt-1 text-3xl font-bold text-pg-warning">{{ stats.pois_pending }}</p>
            </div>
            <div class="pg-card p-5">
                <p class="text-sm text-pg-muted">Contributi da moderare</p>
                <p class="mt-1 text-3xl font-bold text-pg-error">{{ stats.contributions_pending }}</p>
            </div>
            <div class="pg-card p-5">
                <p class="text-sm text-pg-muted">Eventi attivi</p>
                <p class="mt-1 text-3xl font-bold">{{ stats.events_published }}</p>
            </div>
            <div class="pg-card border-pg-accent/30 bg-amber-50/50 p-5">
                <p class="text-sm text-pg-muted">Sponsor attivi</p>
                <p class="mt-1 text-3xl font-bold text-amber-700">{{ stats.sponsorships_active }}</p>
                <p class="text-xs text-pg-muted">su {{ stats.sponsorships_total }} totali</p>
            </div>
            <div class="pg-card border-pg-accent/30 bg-amber-50/50 p-5 sm:col-span-2">
                <p class="text-sm text-pg-muted">Incassi sponsorizzazioni</p>
                <p class="mt-1 text-3xl font-bold text-amber-700">
                    € {{ Number(stats.sponsorships_revenue).toFixed(2) }}
                </p>
            </div>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-2">
            <Link :href="route('admin.pois.index')" class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-pg-primary/10">
                    <PgIcon name="location" class="h-6 w-6 text-pg-primary" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Gestisci POI</p>
                    <p class="text-sm text-pg-muted">Tabella ordinata con modifica ed eliminazione</p>
                </div>
            </Link>
            <Link :href="route('admin.contributions.index')" class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50">
                    <PgIcon name="filter" class="h-6 w-6 text-pg-error" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Moderazione</p>
                    <p class="text-sm text-pg-muted">{{ stats.contributions_pending }} contributi in attesa</p>
                </div>
            </Link>
            <Link :href="route('admin.sponsorships.create')" class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-pg-accent/20">
                    <PgIcon name="plus" class="h-6 w-6 text-amber-700" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Nuova sponsorizzazione</p>
                    <p class="text-sm text-pg-muted">Aggiungi contenuto sponsorizzato con date e importo</p>
                </div>
            </Link>
            <Link :href="route('admin.sponsorships.index')" class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-pg-primary/10">
                    <PgIcon name="star" class="h-6 w-6 text-pg-primary" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Gestisci sponsorizzazioni</p>
                    <p class="text-sm text-pg-muted">Modifica, disattiva o elimina campagne</p>
                </div>
            </Link>
            <Link :href="route('admin.events.index')" class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50">
                    <PgIcon name="bell" class="h-6 w-6 text-blue-600" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Eventi</p>
                    <p class="text-sm text-pg-muted">Crea e gestisci eventi in evidenza</p>
                </div>
            </Link>
            <Link :href="route('admin.itineraries.index')" class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50">
                    <PgIcon name="route" class="h-6 w-6 text-purple-600" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Itinerari</p>
                    <p class="text-sm text-pg-muted">Percorsi con tappe POI</p>
                </div>
            </Link>
            <Link
                v-if="isSuperAdmin"
                :href="route('admin.system.index')"
                class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50">
                    <PgIcon name="alert" class="h-6 w-6 text-emerald-700" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Monitoraggio sistema</p>
                    <p class="text-sm text-pg-muted">Email, backup, salute applicazione</p>
                </div>
            </Link>
            <Link
                v-if="isSuperAdmin"
                :href="route('admin.settings.edit')"
                class="pg-card flex items-center gap-4 p-6 transition hover:shadow-md"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100">
                    <PgIcon name="clock" class="h-6 w-6 text-pg-muted" />
                </div>
                <div>
                    <p class="font-semibold text-pg-text">Impostazioni globali</p>
                    <p class="text-sm text-pg-muted">Legali, social, PayPal e centro mappa</p>
                </div>
            </Link>
        </div>
    </AdminShell>
</template>
