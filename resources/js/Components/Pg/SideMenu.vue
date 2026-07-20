<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

const props = defineProps({
    open: Boolean,
});

const emit = defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => ['admin', 'superadmin'].includes(user.value?.role));
const eventsPublic = computed(() => page.props.features?.events_public ?? false);

const links = computed(() => {
    const items = [
        { href: 'home', label: 'Esplora', icon: 'compass' },
        { href: 'poi.index', label: 'Lista luoghi', icon: 'list' },
        { href: 'filters', label: 'Filtri', icon: 'filter' },
        { href: 'favorites', label: 'Preferiti', icon: 'heart', auth: true },
        { href: 'routes', label: 'Itinerari', icon: 'route' },
    ];
    if (eventsPublic.value) {
        items.push({ href: 'events.index', label: 'Eventi', icon: 'bell' });
    }
    items.push(
        { href: 'contribute.create', label: 'Aggiungi luogo', icon: 'plus', auth: true },
        { href: 'profile.edit', label: 'Profilo', icon: 'user', auth: true },
    );
    return items;
});
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="open"
                class="fixed inset-0 z-[600] bg-black/40 backdrop-blur-sm"
                @click="emit('close')"
            />
        </Transition>

        <Transition name="slide">
            <aside
                v-if="open"
                class="fixed bottom-0 left-0 top-0 z-[700] flex w-72 max-w-[85vw] flex-col bg-pg-surface shadow-2xl"
            >
                <div class="border-b border-gray-100 px-5 py-6">
                    <Link :href="route('home')" class="text-2xl font-bold text-pg-primary" @click="emit('close')">
                        PG Spot
                    </Link>
                    <p class="mt-1 text-sm text-pg-muted">La mappa di Perugia</p>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-4">
                    <template v-for="item in links" :key="item.href">
                        <Link
                            v-if="!item.auth || user"
                            :href="route(item.href)"
                            class="mb-1 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-pg-text transition hover:bg-pg-primary/5"
                            @click="emit('close')"
                        >
                            <PgIcon :name="item.icon" class="h-5 w-5 text-pg-primary" />
                            {{ item.label }}
                        </Link>
                        <Link
                            v-else
                            :href="route('login')"
                            class="mb-1 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-pg-muted"
                            @click="emit('close')"
                        >
                            <PgIcon :name="item.icon" class="h-5 w-5" />
                            {{ item.label }}
                            <span class="ml-auto text-xs text-pg-primary">Accedi</span>
                        </Link>
                    </template>

                    <Link
                        v-if="isAdmin"
                        :href="route('admin.dashboard')"
                        class="mb-1 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-pg-text hover:bg-pg-primary/5"
                        @click="emit('close')"
                    >
                        <PgIcon name="filter" class="h-5 w-5 text-pg-warning" />
                        Pannello Admin
                    </Link>

                    <div class="my-3 border-t border-gray-100 pt-3">
                        <p class="mb-2 px-4 text-xs font-semibold uppercase tracking-wide text-pg-muted">Legale</p>
                        <Link
                            v-for="legal in ['privacy', 'termini', 'cookie', 'contatti']"
                            :key="legal"
                            :href="route('legal.show', legal)"
                            class="mb-1 block rounded-xl px-4 py-2 text-sm text-pg-muted hover:bg-pg-primary/5 hover:text-pg-text"
                            @click="emit('close')"
                        >
                            {{ legal.charAt(0).toUpperCase() + legal.slice(1) }}
                        </Link>
                    </div>
                </nav>

                <div class="border-t border-gray-100 p-4">
                    <template v-if="user">
                        <p class="truncate text-sm font-medium">{{ user.name }}</p>
                        <p class="truncate text-xs text-pg-muted">{{ user.email }}</p>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="mt-3 w-full rounded-xl py-2.5 text-sm font-medium text-pg-error hover:bg-red-50"
                            @click="emit('close')"
                        >
                            Esci
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="pg-btn-primary mb-2 w-full" @click="emit('close')">
                            Accedi
                        </Link>
                        <Link :href="route('register')" class="pg-btn-outline w-full" @click="emit('close')">
                            Registrati
                        </Link>
                    </template>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from,
.slide-leave-to {
    transform: translateX(-100%);
}
</style>
