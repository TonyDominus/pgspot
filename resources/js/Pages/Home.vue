<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import SearchHeader from '@/Components/Pg/SearchHeader.vue';
import CategoryChips from '@/Components/Pg/CategoryChips.vue';
import MapView from '@/Components/Pg/MapView.vue';
import BottomSheet from '@/Components/Pg/BottomSheet.vue';
import PoiListCard from '@/Components/Pg/PoiListCard.vue';
import PoiPreviewPanel from '@/Components/Pg/PoiPreviewPanel.vue';
import SponsoredCard from '@/Components/Pg/SponsoredCard.vue';
import PgIcon from '@/Components/Icons/PgIcon.vue';
import SeoHead from '@/Components/Pg/SeoHead.vue';
import { withDistance } from '@/utils/geo';

const props = defineProps({
    categories: Array,
    pois: Array,
    featuredEvents: Array,
    activeCategory: String,
    search: String,
    mapCenter: Object,
    canContribute: Boolean,
    sponsorships: Array,
    featuredSponsorships: Array,
    seo: Object,
});

const sponsoredPoiIds = computed(() =>
    (props.sponsorships ?? [])
        .filter((s) => s.poi_id)
        .map((s) => s.poi_id),
);

const searchQuery = ref(props.search ?? '');
const userLocation = ref(null);
const mapRef = ref(null);
const selectedSlug = ref(null);

const poisWithDistance = computed(() => withDistance(props.pois, userLocation.value));

const filteredPois = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return poisWithDistance.value;

    return poisWithDistance.value.filter(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.description?.toLowerCase().includes(q) ||
            p.address?.toLowerCase().includes(q),
    );
});

const selectedPoi = computed(() =>
    filteredPois.value.find((p) => p.slug === selectedSlug.value) ?? null,
);

function filterCategory(slug) {
    router.get(route('home'), buildParams({ cat: slug || undefined }), {
        preserveState: true,
        preserveScroll: true,
    });
}

function buildParams(extra = {}) {
    const params = { ...extra };
    if (searchQuery.value) params.q = searchQuery.value;
    return params;
}

function locateUser() {
    if (!navigator.geolocation) return;

    navigator.geolocation.getCurrentPosition((pos) => {
        userLocation.value = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
        };
        mapRef.value?.flyTo(pos.coords.latitude, pos.coords.longitude);
    });
}

function onMapSelect(poi) {
    selectedSlug.value = poi.slug;
    mapRef.value?.flyTo(Number(poi.latitude), Number(poi.longitude));
}

function closePreview() {
    selectedSlug.value = null;
}

let searchTimeout;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('home'), buildParams({ cat: props.activeCategory || undefined, q: val || undefined }), {
            preserveState: true,
            replace: true,
        });
    }, 400);
});
</script>

<template>
    <SeoHead v-if="seo" :seo="seo" />
    <Head v-else title="Esplora" />

    <AppShell active-nav="explore" full-width hide-footer no-padding v-slot="{ openMenu }">
            <div class="relative flex h-[100dvh] flex-col lg:grid lg:h-[calc(100dvh)] lg:grid-cols-[1fr_380px] xl:grid-cols-[1fr_420px]">
                <!-- Mappa -->
                <div class="relative min-h-0 flex-1">
                    <SearchHeader
                        v-model:search="searchQuery"
                        floating
                        @open-menu="openMenu"
                    >
                        <CategoryChips
                            :categories="categories"
                            :active-category="activeCategory"
                            @select="filterCategory"
                        />
                    </SearchHeader>

                    <MapView
                        ref="mapRef"
                        class="absolute inset-0 lg:relative lg:h-full"
                        :pois="filteredPois"
                        :sponsored-poi-ids="sponsoredPoiIds"
                        :center="mapCenter"
                        :zoom="mapCenter?.zoom ?? 14"
                        :active-slug="selectedSlug"
                        @select="onMapSelect"
                    />

                    <div class="absolute right-4 top-28 z-[400] flex flex-col gap-2 lg:top-4">
                        <button
                            type="button"
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-pg-surface/95 text-pg-primary shadow-card backdrop-blur-sm"
                            aria-label="La mia posizione"
                            @click="locateUser"
                        >
                            <PgIcon name="location" class="h-5 w-5" />
                        </button>
                        <Link
                            :href="canContribute ? route('contribute.create') : route('login')"
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-pg-primary text-white shadow-fab lg:hidden"
                            aria-label="Aggiungi"
                        >
                            <PgIcon name="plus" class="h-5 w-5" />
                        </Link>
                    </div>

                    <div class="absolute left-4 top-28 z-[400] lg:top-4">
                        <Link
                            :href="route('filters', { cat: activeCategory })"
                            class="flex items-center gap-2 rounded-full bg-pg-surface/95 px-4 py-2 text-sm font-medium text-pg-text shadow-card backdrop-blur-sm"
                        >
                            <PgIcon name="filter" class="h-4 w-4" />
                            Filtri
                        </Link>
                    </div>

                    <div
                        v-if="featuredEvents?.length"
                        class="absolute left-4 right-4 top-36 z-[400] lg:left-auto lg:right-4 lg:top-16 lg:max-w-xs"
                    >
                        <div
                            v-for="event in featuredEvents.slice(0, 1)"
                            :key="event.id"
                        >
                            <Link
                                :href="route('events.index')"
                                class="block rounded-2xl bg-pg-primary/95 p-3 text-white shadow-card backdrop-blur-sm transition hover:bg-pg-primary"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide opacity-80">Evento</p>
                                <p class="mt-0.5 font-semibold">{{ event.title }}</p>
                                <p v-if="event.description" class="mt-1 line-clamp-2 text-xs opacity-90">{{ event.description }}</p>
                                <p class="mt-2 text-xs underline opacity-90">Tutti gli eventi →</p>
                            </Link>
                        </div>
                    </div>

                    <!-- Pannello info POI su tap (sopra il bottom sheet) -->
                    <PoiPreviewPanel
                        :poi="selectedPoi"
                        :visible="!!selectedPoi"
                        @close="closePreview"
                    />

                    <!-- Mobile bottom sheet — nascosto quando c'è anteprima POI -->
                    <div v-show="!selectedPoi" class="lg:hidden">
                        <BottomSheet :pois="filteredPois" :sponsorships="sponsorships ?? []" />
                    </div>
                </div>

                <!-- Desktop pannello laterale -->
                <aside class="hidden min-h-0 flex-col border-l border-gray-100 bg-pg-surface lg:flex">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="font-semibold text-pg-text">Vicino a te</h2>
                            <p class="text-xs text-pg-muted">{{ filteredPois.length }} luoghi</p>
                        </div>
                        <Link
                            :href="route('poi.index', { cat: activeCategory, q: searchQuery || undefined })"
                            class="text-sm font-medium text-pg-primary"
                        >
                            Lista →
                        </Link>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto p-4">
                        <div v-if="featuredSponsorships?.length" class="mb-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700">In collaborazione con</p>
                            <div class="space-y-3">
                                <SponsoredCard
                                    v-for="s in featuredSponsorships"
                                    :key="s.id"
                                    :sponsorship="s"
                                    class="!w-full"
                                />
                            </div>
                        </div>
                        <div v-if="sponsorships?.length" class="mb-4 space-y-3">
                            <SponsoredCard
                                v-for="s in sponsorships"
                                :key="s.id"
                                :sponsorship="s"
                                class="!w-full"
                            />
                        </div>
                        <div v-if="filteredPois.length === 0 && !sponsorships?.length" class="py-12 text-center text-sm text-pg-muted">
                            Nessun luogo trovato.
                        </div>
                        <div v-else class="space-y-3">
                            <PoiListCard
                                v-for="poi in filteredPois"
                                :key="poi.id"
                                :poi="poi"
                            />
                        </div>
                    </div>
                </aside>
            </div>
    </AppShell>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}
.list-enter-from {
    opacity: 0;
    transform: translateX(-12px);
}
.list-leave-to {
    opacity: 0;
    transform: translateX(12px);
}
</style>
