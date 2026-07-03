<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    active: { type: String, default: 'explore' },
});

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => ['admin', 'superadmin'].includes(user.value?.role));

const items = [
    { id: 'explore', href: 'home', icon: 'compass', label: 'Esplora' },
    { id: 'favorites', href: 'favorites', icon: 'heart', label: 'Preferiti', auth: true },
    { id: 'contribute', href: 'contribute.create', icon: 'plus', label: 'Aggiungi', auth: true, fab: true },
    { id: 'routes', href: 'routes', icon: 'route', label: 'Itinerari' },
    { id: 'profile', href: 'profile.edit', icon: 'user', label: 'Profilo', auth: true },
];
</script>

<template>
    <nav class="hidden w-56 shrink-0 flex-col border-r border-gray-100 bg-pg-surface py-6 lg:flex xl:w-64">
        <div class="mb-8 px-5">
            <Link :href="route('home')" class="text-xl font-bold text-pg-primary">PG Spot</Link>
            <p class="text-xs text-pg-muted">Perugia</p>
        </div>

        <div class="flex flex-1 flex-col gap-1 px-3">
            <template v-for="item in items" :key="item.id">
                <Link
                    v-if="!item.fab && (!item.auth || isLoggedIn)"
                    :href="route(item.href)"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition"
                    :class="active === item.id ? 'bg-pg-primary/10 text-pg-primary' : 'text-pg-muted hover:bg-gray-50 hover:text-pg-text'"
                >
                    <PgIcon :name="item.icon" class="h-5 w-5" />
                    {{ item.label }}
                </Link>
                <Link
                    v-else-if="item.fab"
                    :href="isLoggedIn ? route(item.href) : route('login')"
                    class="mx-3 my-2 flex items-center justify-center gap-2 rounded-xl bg-pg-primary py-3 text-sm font-semibold text-white shadow-fab transition hover:bg-pg-primary-dark"
                >
                    <PgIcon name="plus" class="h-5 w-5" />
                    Aggiungi
                </Link>
                <Link
                    v-else-if="item.auth && !isLoggedIn"
                    :href="route('login')"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-pg-muted hover:bg-gray-50"
                >
                    <PgIcon :name="item.icon" class="h-5 w-5" />
                    {{ item.label }}
                </Link>
            </template>

            <Link
                v-if="isAdmin"
                :href="route('admin.dashboard')"
                class="mt-2 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-pg-warning hover:bg-amber-50"
            >
                <PgIcon name="filter" class="h-5 w-5" />
                Admin
            </Link>
        </div>

        <div class="mt-auto space-y-2 border-t border-gray-100 px-4 pt-4">
            <template v-if="isLoggedIn">
                <p class="truncate px-2 text-xs text-pg-muted">{{ user?.name }}</p>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-pg-error transition hover:bg-red-50"
                >
                    <PgIcon name="close" class="h-4 w-4" />
                    Esci
                </Link>
            </template>
            <template v-else>
                <Link :href="route('login')" class="pg-btn-primary w-full text-center text-sm">Accedi</Link>
            </template>
        </div>
    </nav>
</template>
