<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-pg-background px-4 pt-8 sm:justify-center sm:pt-0">
        <div class="mb-6 text-center">
            <Link href="/" class="text-3xl font-bold text-pg-primary">PG Spot</Link>
            <p class="mt-1 text-sm text-pg-muted">La mappa collaborativa di Perugia</p>
        </div>

        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-pg-surface px-6 py-6 shadow-card">
            <slot />
        </div>
    </div>
</template>
