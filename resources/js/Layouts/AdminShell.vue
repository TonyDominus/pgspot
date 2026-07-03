<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import PageTransition from '@/Components/Pg/PageTransition.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const navItems = [
    { href: 'admin.dashboard', icon: 'compass', label: 'Dashboard' },
    { href: 'admin.sponsorships.index', icon: 'star', label: 'Sponsorizzazioni' },
];
</script>

<template>
    <div class="flex min-h-screen bg-pg-background">
        <aside class="hidden w-60 shrink-0 flex-col border-r border-gray-100 bg-pg-surface lg:flex xl:w-64">
            <div class="border-b border-gray-100 px-5 py-6">
                <Link :href="route('home')" class="text-xl font-bold text-pg-primary">PG Spot</Link>
                <p class="text-xs font-medium text-pg-warning">Pannello Admin</p>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="route(item.href)"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition"
                    :class="route().current(item.href) || route().current(item.href.replace('.index', '.*'))
                        ? 'bg-pg-primary/10 text-pg-primary'
                        : 'text-pg-muted hover:bg-gray-50'"
                >
                    <PgIcon :name="item.icon" class="h-5 w-5" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="space-y-2 border-t border-gray-100 p-4">
                <p class="truncate text-xs text-pg-muted">{{ user?.name }}</p>
                <Link :href="route('home')" class="pg-btn-outline w-full text-center text-sm">
                    ← Torna al sito
                </Link>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full rounded-xl px-4 py-2.5 text-sm font-medium text-pg-error transition hover:bg-red-50"
                >
                    Esci
                </Link>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center gap-3 border-b border-gray-100 bg-pg-surface px-4 py-4 lg:hidden">
                <Link :href="route('admin.dashboard')" class="font-bold text-pg-primary">Admin</Link>
                <div class="flex-1" />
                <Link :href="route('home')" class="text-sm text-pg-primary">Sito</Link>
                <Link :href="route('logout')" method="post" as="button" class="text-sm text-pg-error">Esci</Link>
            </header>

            <PageTransition>
                <main class="mx-auto w-full max-w-6xl flex-1 p-4 lg:p-8">
                    <slot />
                </main>
            </PageTransition>
        </div>
    </div>
</template>
