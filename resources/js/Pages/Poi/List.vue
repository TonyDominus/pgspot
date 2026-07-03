<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import { withDistance } from '@/utils/geo';

const props = defineProps({
    categories: Array,
    pois: Array,
    activeCategory: String,
    search: String,
    mapCenter: Object,
});

const searchQuery = ref(props.search ?? '');
const userLocation = ref(null);

const categoryName = computed(() => {
    if (!props.activeCategory) return 'Tutti i luoghi';
    return props.categories.find((c) => c.slug === props.activeCategory)?.name ?? 'Luoghi';
});

const poisWithDistance = computed(() => withDistance(props.pois, userLocation.value));

function goBack() {
    router.get(route('home'), { cat: props.activeCategory || undefined });
}

let searchTimeout;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('poi.index'),
            { cat: props.activeCategory || undefined, q: val || undefined },
            { preserveState: true, replace: true },
        );
    }, 400);
});
</script>

<template>
    <Head :title="categoryName" />

    <AppShell active-nav="explore" v-slot="{ openMenu }">
        <header class="sticky top-0 z-20 bg-pg-surface px-4 py-4 shadow-sm lg:rounded-2xl lg:shadow-card">
            <div class="mb-3 flex items-center gap-3">
                <button type="button" class="rounded-full p-1 lg:hidden" @click="openMenu">
                    <PgIcon name="menu" class="h-6 w-6" />
                </button>
                <button type="button" class="rounded-full p-1" @click="goBack">
                    <PgIcon name="back" class="h-6 w-6" />
                </button>
                <h1 class="flex-1 text-lg font-semibold">{{ categoryName }}</h1>
                <Link
                    :href="route('filters', { cat: activeCategory })"
                    class="rounded-full p-2 text-pg-muted hover:bg-gray-100"
                >
                    <PgIcon name="filter" class="h-5 w-5" />
                </Link>
            </div>

            <div class="mb-3 flex rounded-xl bg-gray-100 p-1">
                <Link
                    :href="route('home', { cat: activeCategory })"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium text-pg-muted"
                >
                    <PgIcon name="map" class="h-4 w-4" />
                    Mappa
                </Link>
                <span class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-pg-surface py-2 text-sm font-semibold text-pg-primary shadow-sm">
                    <PgIcon name="list" class="h-4 w-4" />
                    Lista
                </span>
            </div>

            <input
                v-model="searchQuery"
                type="search"
                placeholder="Cerca..."
                class="pg-input rounded-full"
            />
        </header>

        <main class="space-y-3 px-4 py-4">
            <p class="text-sm text-pg-muted">{{ poisWithDistance.length }} risultati</p>

            <PoiListCard
                v-for="poi in poisWithDistance"
                :key="poi.id"
                :poi="poi"
            />

            <div v-if="poisWithDistance.length === 0" class="py-12 text-center text-pg-muted">
                Nessun risultato.
            </div>
        </main>
    </AppShell>
</template>
