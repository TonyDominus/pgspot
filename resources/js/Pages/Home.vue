<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
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

const props = defineProps({
    categories: Array,
    filterTags: Array,
    filters: Object,
    focus: Object,
    featuredEvents: Array,
    mapCenter: Object,
    canContribute: Boolean,
    sponsorships: Array,
    featuredSponsorships: Array,
    seo: Object,
    itinerary: Object,
});

const sponsoredPoiIds = computed(() =>
    (props.sponsorships ?? [])
        .filter((s) => s.poi_id)
        .map((s) => s.poi_id),
);

const searchQuery = ref('');
const suggestions = ref({ pois: [], municipalities: [], categories: [] });
const suggestionsOpen = ref(false);
const suggestionIndex = ref(-1);
const markers = ref([]);
const truncated = ref(false);
const mapError = ref('');
const searchError = ref('');
const gpsState = ref('idle');
const gpsMessage = ref('');
const userLocation = ref(null);
const radius = ref(null);
const mapRef = ref(null);
const selectedSlug = ref(props.focus?.slug ?? null);
const revealedFocus = ref(null);
const filtersOpen = ref(false);
const draft = ref(emptyDraft());

const listPois = computed(() => markers.value.slice(0, 40));
const selectedPoi = computed(() => markers.value.find((poi) => poi.slug === selectedSlug.value) ?? (props.focus?.slug === selectedSlug.value ? props.focus : null));
const itineraryMode = computed(() => !!props.itinerary?.stops?.length);
const areaLabel = computed(() => {
    if (itineraryMode.value) return props.itinerary.title;
    return gpsState.value === 'granted' ? 'Vicino a te' : 'Luoghi in zona';
});
const activeFilterCount = computed(() => {
    const filters = props.filters ?? {};
    return [filters.category, filters.municipality, filters.free, filters.parking, filters.rating, filters.tags?.length, filters.access?.length].filter(Boolean).length;
});
const flatSuggestions = computed(() => [
    ...suggestions.value.pois.map((item) => ({ ...item, group: 'Luoghi' })),
    ...suggestions.value.municipalities.map((item) => ({ ...item, group: 'Comuni' })),
    ...suggestions.value.categories.map((item) => ({ ...item, group: 'Categorie' })),
]);

function emptyDraft() {
    return {
        municipality: props.filters?.municipality?.istat_code ?? '',
        category: props.filters?.category ?? '',
        tags: [...(props.filters?.tags ?? [])],
        free: !!props.filters?.free,
        access: [...(props.filters?.access ?? [])],
        parking: !!props.filters?.parking,
        rating: props.filters?.rating ?? 0,
    };
}

function queryFromFilters(extra = {}) {
    const filters = props.filters ?? {};
    const params = { ...extra };
    if (filters.category) params.category = filters.category;
    if (filters.municipality?.istat_code) params.municipality = filters.municipality.istat_code;
    if (filters.tags?.length) params.tag = filters.tags.join(',');
    if (filters.free) params.free = 1;
    if (filters.access?.length) params.access = filters.access.join(',');
    if (filters.parking) params.parking = 1;
    if (filters.rating) params.rating = filters.rating;
    return params;
}

function visitFilters(params) {
    router.get(route('home'), params, { preserveState: true, preserveScroll: true, replace: true });
}

function filterCategory(slug) {
    const params = queryFromFilters();
    if (slug && slug !== props.filters?.category) params.category = slug;
    else delete params.category;
    visitFilters(params);
}

function applyDraft() {
    const params = {};
    if (draft.value.category) params.category = draft.value.category;
    if (draft.value.municipality) params.municipality = draft.value.municipality;
    if (draft.value.tags.length) params.tag = draft.value.tags.join(',');
    if (draft.value.free) params.free = 1;
    if (draft.value.access.length) params.access = draft.value.access.join(',');
    if (draft.value.parking) params.parking = 1;
    if (draft.value.rating) params.rating = draft.value.rating;
    filtersOpen.value = false;
    visitFilters(params);
}

function clearFilters() {
    draft.value = { municipality: '', category: '', tags: [], free: false, access: [], parking: false, rating: 0 };
    filtersOpen.value = false;
    visitFilters({});
}

function toggleDraftTag(slug) {
    const index = draft.value.tags.indexOf(slug);
    if (index >= 0) draft.value.tags.splice(index, 1);
    else draft.value.tags.push(slug);
}

function toggleAccess(value) {
    const index = draft.value.access.indexOf(value);
    if (index >= 0) draft.value.access.splice(index, 1);
    else draft.value.access.push(value);
}

let searchTimer;
watch(searchQuery, (value) => {
    clearTimeout(searchTimer);
    suggestionIndex.value = -1;
    if (value.trim().length < 2) {
        suggestions.value = { pois: [], municipalities: [], categories: [] };
        suggestionsOpen.value = false;
        return;
    }
    searchTimer = setTimeout(async () => {
        try {
            const response = await fetch(`${route('search.suggestions')}?q=${encodeURIComponent(value.trim())}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('search');
            suggestions.value = await response.json();
            suggestionsOpen.value = true;
            searchError.value = '';
        } catch {
            searchError.value = 'Ricerca non disponibile.';
            suggestionsOpen.value = false;
        }
    }, 250);
});

function onSearchKey(event) {
    if (event.key === 'Escape') {
        suggestionsOpen.value = false;
        return;
    }
    if (!suggestionsOpen.value || !flatSuggestions.value.length) return;
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        suggestionIndex.value = Math.min(flatSuggestions.value.length - 1, suggestionIndex.value + 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        suggestionIndex.value = Math.max(0, suggestionIndex.value - 1);
    } else if (event.key === 'Enter' && suggestionIndex.value >= 0) {
        event.preventDefault();
        chooseSuggestion(flatSuggestions.value[suggestionIndex.value]);
    }
}

function chooseSuggestion(item) {
    suggestionsOpen.value = false;
    searchQuery.value = '';
    if (item.type === 'poi') {
        router.visit(route('poi.show', item.slug));
        return;
    }
    if (item.type === 'municipality') {
        visitFilters({ ...queryFromFilters(), municipality: item.istat_code });
        if (item.latitude && item.longitude) {
            nextTick(() => mapRef.value?.flyTo(Number(item.latitude), Number(item.longitude)));
        }
        return;
    }
    if (item.type === 'category') {
        visitFilters({ ...queryFromFilters(), category: item.slug });
    }
}

const lastBounds = ref(null);
watch(radius, () => {
    if (lastBounds.value) loadMarkers(lastBounds.value);
});

function exitItinerary() {
    visitFilters(queryFromFilters());
}

async function loadMarkers(bounds) {
    lastBounds.value = bounds;
    if (itineraryMode.value) {
        markers.value = props.itinerary.stops;
        truncated.value = false;
        mapError.value = '';
        nextTick(() => mapRef.value?.fitToStops?.());
        return;
    }
    const params = new URLSearchParams({
        south: bounds.south,
        north: bounds.north,
        west: bounds.west,
        east: bounds.east,
    });
    const filters = props.filters ?? {};
    if (filters.category) params.set('category', filters.category);
    if (filters.municipality?.istat_code) params.set('municipality', filters.municipality.istat_code);
    if (filters.tags?.length) params.set('tag', filters.tags.join(','));
    if (filters.free) params.set('free', '1');
    if (filters.access?.length) params.set('access', filters.access.join(','));
    if (filters.parking) params.set('parking', '1');
    if (filters.rating) params.set('rating', String(filters.rating));
    if (gpsState.value === 'granted' && userLocation.value && radius.value) {
        params.set('lat', String(userLocation.value.lat));
        params.set('lng', String(userLocation.value.lng));
        params.set('radius', String(radius.value));
    }
    try {
        const response = await fetch(`${route('map.pois')}?${params}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('map');
        const data = await response.json();
        markers.value = data.markers ?? [];
        truncated.value = !!data.truncated;
        mapError.value = '';
        revealFocusIfNeeded();
    } catch {
        mapError.value = 'Mappa non aggiornata. Riprova spostandola.';
    }
}

function locateUser() {
    if (!navigator.geolocation) {
        gpsState.value = 'error';
        gpsMessage.value = 'Questo browser non fornisce la posizione.';
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            gpsState.value = 'granted';
            gpsMessage.value = '';
            userLocation.value = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            radius.value = radius.value ?? 5;
            mapRef.value?.flyTo(pos.coords.latitude, pos.coords.longitude);
        },
        (error) => {
            userLocation.value = null;
            radius.value = null;
            if (error.code === 1) {
                gpsState.value = 'denied';
                gpsMessage.value = 'Posizione negata. I luoghi restano quelli in mappa.';
            } else {
                gpsState.value = 'error';
                gpsMessage.value = 'Posizione non disponibile.';
            }
        },
        { enableHighAccuracy: false, timeout: 8000 },
    );
}

function onMapSelect(poi) {
    selectedSlug.value = poi.slug;
    if (itineraryMode.value) {
        mapRef.value?.flyTo(Number(poi.latitude), Number(poi.longitude));
    }
}

function closePreview() {
    selectedSlug.value = null;
}

function revealFocusIfNeeded() {
    if (itineraryMode.value || !props.focus?.slug || revealedFocus.value === props.focus.slug) {
        return;
    }
    const found = markers.value.find((poi) => poi.slug === props.focus.slug);
    if (!found) {
        return;
    }
    revealedFocus.value = props.focus.slug;
    nextTick(() => mapRef.value?.revealSlug?.(
        found.slug,
        Number(found.latitude),
        Number(found.longitude),
    ));
}

function applyFocus() {
    if (!props.focus?.latitude) return;
    selectedSlug.value = props.focus.slug;
    revealedFocus.value = null;
    nextTick(() => mapRef.value?.revealSlug?.(
        props.focus.slug,
        Number(props.focus.latitude),
        Number(props.focus.longitude),
    ));
}

onMounted(applyFocus);
watch(() => props.focus, applyFocus);
watch(() => props.filters, () => {
    draft.value = emptyDraft();
}, { deep: true });
watch(() => props.itinerary, () => {
    if (lastBounds.value) loadMarkers(lastBounds.value);
    else if (itineraryMode.value) {
        markers.value = props.itinerary.stops;
        nextTick(() => mapRef.value?.fitToStops?.());
    }
}, { deep: true });
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
                        @focus-search="suggestionsOpen = searchQuery.trim().length >= 2"
                        @search-key="onSearchKey"
                    >
                        <template #suggestions>
                            <div
                                v-if="suggestionsOpen"
                                class="absolute left-0 right-0 top-12 z-50 max-h-80 overflow-y-auto rounded-2xl bg-pg-surface py-2 text-left shadow-card"
                                @mousedown.prevent
                            >
                                <p v-if="searchError" class="px-3 py-2 text-sm text-pg-muted">{{ searchError }}</p>
                                <p v-else-if="!flatSuggestions.length" class="px-3 py-2 text-sm text-pg-muted">Nessun risultato</p>
                                <button
                                    v-for="(item, index) in flatSuggestions"
                                    :key="`${item.type}-${item.id}`"
                                    type="button"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50"
                                    :class="index === suggestionIndex ? 'bg-gray-50' : ''"
                                    @click="chooseSuggestion(item)"
                                >
                                    <span class="text-[10px] uppercase text-pg-muted">{{ item.group }}</span>
                                    <span class="block font-medium">{{ item.name }}</span>
                                </button>
                            </div>
                        </template>
                        <CategoryChips
                            :categories="categories"
                            :active-category="filters?.category"
                            @select="filterCategory"
                        />
                    </SearchHeader>

                    <MapView
                        ref="mapRef"
                        class="absolute inset-0 lg:relative lg:h-full"
                        :pois="markers"
                        :sponsored-poi-ids="sponsoredPoiIds"
                        :center="mapCenter"
                        :zoom="mapCenter?.zoom ?? 14"
                        :active-slug="selectedSlug"
                        :user-location="userLocation"
                        :numbered="itineraryMode"
                        :show-line="itineraryMode"
                        :cluster="!itineraryMode"
                        @select="onMapSelect"
                        @moveend="loadMarkers"
                    />

                    <div v-if="itineraryMode" class="absolute left-4 right-4 top-28 z-[400] flex items-center justify-between gap-2 rounded-2xl bg-pg-surface/95 px-3 py-2 shadow-card lg:top-4 lg:max-w-md">
                        <div>
                            <p class="text-sm font-semibold">{{ itinerary.title }}</p>
                            <p class="text-xs text-pg-muted">Ordine delle tappe, non un percorso stradale.</p>
                        </div>
                        <button type="button" class="text-sm font-medium text-pg-primary" @click="exitItinerary">Esci dall'itinerario</button>
                    </div>

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
                            class="flex h-11 items-center gap-1 rounded-full bg-pg-primary px-4 text-sm font-medium text-white shadow-fab lg:hidden"
                            aria-label="Aggiungi spot"
                        >
                            <PgIcon name="plus" class="h-5 w-5" />
                            Aggiungi spot
                        </Link>
                    </div>

                    <div class="absolute left-4 top-28 z-[400] lg:top-4">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-full bg-pg-surface/95 px-4 py-2 text-sm font-medium text-pg-text shadow-card backdrop-blur-sm"
                            :aria-expanded="filtersOpen"
                            @click="filtersOpen = !filtersOpen"
                        >
                            <PgIcon name="filter" class="h-4 w-4" />
                            Filtri
                            <span v-if="activeFilterCount" class="rounded-full bg-pg-primary px-1.5 text-xs text-white">{{ activeFilterCount }}</span>
                        </button>
                    </div>
                    <p v-if="gpsMessage" class="absolute bottom-24 left-4 z-[400] max-w-xs rounded-xl bg-pg-surface px-3 py-2 text-xs text-pg-text shadow-card" role="status">{{ gpsMessage }}</p>
                    <p v-if="mapError" class="absolute bottom-36 left-4 z-[400] max-w-xs rounded-xl bg-pg-surface px-3 py-2 text-xs text-pg-text shadow-card" role="status">{{ mapError }}</p>

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
                        <BottomSheet :pois="listPois" :sponsorships="sponsorships ?? []" :title="areaLabel" />
                    </div>
                </div>

                <!-- Desktop pannello laterale -->
                <aside class="hidden min-h-0 flex-col border-l border-gray-100 bg-pg-surface lg:flex">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="font-semibold text-pg-text">{{ areaLabel }}</h2>
                            <p class="text-xs text-pg-muted">{{ markers.length }} luoghi<span v-if="truncated"> · Zooma per vedere più luoghi</span></p>
                        </div>
                        <Link
                            :href="route('poi.index', { category: filters?.category || undefined })"
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
                        <div v-if="listPois.length === 0 && !sponsorships?.length" class="py-12 text-center text-sm text-pg-muted">
                            Nessun luogo in questa zona.
                        </div>
                        <div v-else class="space-y-3">
                            <PoiListCard
                                v-for="poi in listPois"
                                :key="poi.id"
                                :poi="poi"
                            />
                        </div>
                    </div>
                </aside>
            </div>
        <div v-if="filtersOpen" class="fixed inset-0 z-[700] bg-black/30" @click.self="filtersOpen = false">
            <div class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-3xl bg-pg-surface p-5 lg:inset-y-0 lg:left-auto lg:right-0 lg:w-96 lg:rounded-none" role="dialog" aria-label="Filtri">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Filtri</h2>
                    <button type="button" class="text-sm text-pg-primary" @click="filtersOpen = false">Chiudi</button>
                </div>
                <label class="mb-1 block text-sm font-medium">Comune (codice ISTAT)</label>
                <input v-model="draft.municipality" type="text" class="pg-input mb-4" placeholder="054039" />
                <label class="mb-1 block text-sm font-medium">Categoria</label>
                <select v-model="draft.category" class="pg-input mb-4">
                    <option value="">Tutte</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
                </select>
                <p class="mb-2 text-sm font-medium">Caratteristiche</p>
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="tag in filterTags"
                        :key="tag.id"
                        type="button"
                        class="rounded-full px-3 py-1 text-xs"
                        :class="draft.tags.includes(tag.slug) ? 'bg-pg-primary text-white' : 'bg-gray-100'"
                        @click="toggleDraftTag(tag.slug)"
                    >
                        {{ tag.name }}
                    </button>
                </div>
                <label class="mb-2 flex items-center gap-2 text-sm"><input v-model="draft.free" type="checkbox" /> Solo gratuiti</label>
                <div class="mb-3 flex gap-2 text-sm">
                    <button type="button" class="rounded-full px-3 py-1" :class="draft.access.includes('yes') ? 'bg-pg-primary text-white' : 'bg-gray-100'" @click="toggleAccess('yes')">Accessibile</button>
                    <button type="button" class="rounded-full px-3 py-1" :class="draft.access.includes('partial') ? 'bg-pg-primary text-white' : 'bg-gray-100'" @click="toggleAccess('partial')">Parziale</button>
                </div>
                <label class="mb-3 flex items-center gap-2 text-sm"><input v-model="draft.parking" type="checkbox" /> Parcheggio disponibile</label>
                <label class="mb-1 block text-sm font-medium">Voto minimo</label>
                <select v-model="draft.rating" class="pg-input mb-4">
                    <option :value="0">Qualsiasi</option>
                    <option :value="3">3+</option>
                    <option :value="4">4+</option>
                </select>
                <div v-if="gpsState === 'granted'" class="mb-4">
                    <p class="mb-2 text-sm font-medium">Distanza</p>
                    <div class="flex gap-2">
                        <button v-for="km in [1, 5, 10, 25]" :key="km" type="button" class="rounded-full px-3 py-1 text-sm" :class="radius === km ? 'bg-pg-primary text-white' : 'bg-gray-100'" @click="radius = km">{{ km }} km</button>
                    </div>
                </div>
                <button type="button" class="pg-btn-primary w-full" @click="applyDraft">Mostra risultati</button>
                <button type="button" class="mt-2 w-full text-sm text-pg-muted" @click="clearFilters">Rimuovi tutti</button>
            </div>
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
