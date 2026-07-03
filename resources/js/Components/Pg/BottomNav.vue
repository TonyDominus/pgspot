<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';

defineProps({
    active: { type: String, default: 'explore' },
});

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-gray-100 bg-pg-surface/95 backdrop-blur-md lg:hidden">
        <div class="mx-auto flex max-w-lg items-end justify-around px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2">
            <Link
                :href="route('home')"
                class="flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium"
                :class="active === 'explore' ? 'text-pg-primary' : 'text-pg-muted'"
            >
                <PgIcon name="compass" class="h-6 w-6" />
                Esplora
            </Link>

            <Link
                :href="isLoggedIn ? route('favorites') : route('login')"
                class="flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium"
                :class="active === 'favorites' ? 'text-pg-primary' : 'text-pg-muted'"
            >
                <PgIcon name="heart" class="h-6 w-6" />
                Preferiti
            </Link>

            <Link
                :href="isLoggedIn ? route('contribute.create') : route('login')"
                class="-mt-5 flex h-14 w-14 items-center justify-center rounded-full bg-pg-primary text-white shadow-fab"
                aria-label="Aggiungi luogo"
            >
                <PgIcon name="plus" class="h-7 w-7" />
            </Link>

            <Link
                :href="route('routes')"
                class="flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium"
                :class="active === 'routes' ? 'text-pg-primary' : 'text-pg-muted'"
            >
                <PgIcon name="route" class="h-6 w-6" />
                Itinerari
            </Link>

            <Link
                :href="isLoggedIn ? route('profile.edit') : route('login')"
                class="flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium"
                :class="active === 'profile' ? 'text-pg-primary' : 'text-pg-muted'"
            >
                <PgIcon name="user" class="h-6 w-6" />
                Profilo
            </Link>
        </div>
    </nav>
</template>
