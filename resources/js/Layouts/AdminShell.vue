<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import PageTransition from '@/Components/Pg/PageTransition.vue';
import FlashToast from '@/Components/Pg/FlashToast.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => user.value?.role === 'superadmin');
const mobileNavOpen = ref(false);

const navItems = computed(() => {
    const items = [
        { href: 'admin.dashboard', icon: 'compass', label: 'Dashboard' },
        { href: 'admin.pois.index', icon: 'location', label: 'POI' },
        { href: 'admin.contributions.index', icon: 'filter', label: 'Moderazione' },
        { href: 'admin.reviews.index', icon: 'alert', label: 'Recensioni' },
        { href: 'admin.sponsorships.index', icon: 'star', label: 'Sponsor' },
        { href: 'admin.events.index', icon: 'bell', label: 'Eventi' },
        { href: 'admin.itineraries.index', icon: 'route', label: 'Itinerari' },
    ];
    if (isSuperAdmin.value) {
        items.push({ href: 'admin.users.index', icon: 'user', label: 'Utenti' });
        items.push({ href: 'admin.system.index', icon: 'alert', label: 'Monitoraggio' });
        items.push({ href: 'admin.settings.edit', icon: 'clock', label: 'Impostazioni' });
    }
    return items;
});

function isActive(href) {
    return route().current(href) || route().current(href.replace('.index', '.*').replace('.edit', '.*'));
}
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
                    :class="isActive(item.href)
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
            <header class="border-b border-gray-100 bg-pg-surface lg:hidden">
                <div class="flex items-center gap-3 px-4 py-3">
                    <button
                        type="button"
                        class="rounded-lg p-2 text-pg-text hover:bg-gray-100"
                        aria-label="Menu admin"
                        @click="mobileNavOpen = !mobileNavOpen"
                    >
                        <PgIcon name="filter" class="h-5 w-5" />
                    </button>
                    <Link :href="route('admin.dashboard')" class="font-bold text-pg-primary">Admin</Link>
                    <div class="flex-1" />
                    <Link :href="route('home')" class="text-sm text-pg-primary">Sito</Link>
                    <Link :href="route('logout')" method="post" as="button" class="text-sm text-pg-error">Esci</Link>
                </div>

                <nav
                    v-show="mobileNavOpen"
                    class="flex gap-1 overflow-x-auto border-t border-gray-100 px-2 py-2"
                >
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="route(item.href)"
                        class="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive(item.href)
                            ? 'bg-pg-primary text-white'
                            : 'bg-gray-100 text-pg-muted'"
                        @click="mobileNavOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </header>

            <PageTransition>
                <main class="mx-auto w-full max-w-6xl flex-1 p-4 lg:p-8">
                    <slot />
                </main>
            </PageTransition>
        </div>

        <FlashToast />
    </div>
</template>
