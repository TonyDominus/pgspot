<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';

defineProps({
    title: String,
    content: String,
});
</script>

<template>
    <Head :title="title" />

    <AppShell>
        <header class="bg-pg-surface px-4 py-5 shadow-sm">
            <Link :href="route('home')" class="text-sm text-pg-primary">← Torna alla mappa</Link>
            <h1 class="mt-2 text-xl font-bold text-pg-text">{{ title }}</h1>
        </header>

        <main class="prose prose-sm max-w-none px-4 py-6 text-pg-text">
            <p class="whitespace-pre-line text-sm leading-relaxed text-pg-muted">{{ content }}</p>
        </main>
    </AppShell>
</template>
