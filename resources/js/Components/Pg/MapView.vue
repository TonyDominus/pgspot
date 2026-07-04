<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    pois: { type: Array, default: () => [] },
    center: { type: Object, default: () => ({ lat: 43.1107, lng: 12.3908 }) },
    zoom: { type: Number, default: 14 },
    activeSlug: { type: String, default: null },
    sponsoredPoiIds: { type: Array, default: () => [] },
    class: { type: String, default: '' },
});

const emit = defineEmits(['select']);

const mapEl = ref(null);
const mapStyle = ref('standard');
let map = null;
let markers = [];
let tileLayer = null;

const tileLayers = {
    standard: {
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap',
        label: 'Mappa',
    },
    terrain: {
        url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenTopoMap',
        label: 'Rilievo',
    },
};

onMounted(() => {
    if (!mapEl.value) return;

    map = L.map(mapEl.value, {
        zoomControl: false,
        attributionControl: true,
    }).setView([props.center.lat, props.center.lng], props.zoom);

    setTileLayer(mapStyle.value);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    renderMarkers();
});

onUnmounted(() => {
    map?.remove();
});

watch(() => props.pois, renderMarkers, { deep: true });
watch(() => props.activeSlug, highlightActive);

function setTileLayer(style) {
    if (!map) return;
    if (tileLayer) map.removeLayer(tileLayer);
    const cfg = tileLayers[style];
    tileLayer = L.tileLayer(cfg.url, {
        attribution: cfg.attribution,
        maxZoom: style === 'terrain' ? 17 : 19,
    }).addTo(map);
}

function switchStyle(style) {
    mapStyle.value = style;
    setTileLayer(style);
}

function markerColor(poi) {
    return poi.categories?.[0]?.color ?? '#2E7D32';
}

function renderMarkers() {
    if (!map) return;

    markers.forEach((m) => map.removeLayer(m));
    markers = [];

    props.pois.forEach((poi) => {
        const isSponsored = props.sponsoredPoiIds.includes(poi.id);
        const color = isSponsored ? '#FFB300' : markerColor(poi);
        const marker = L.circleMarker([Number(poi.latitude), Number(poi.longitude)], {
            radius: props.activeSlug === poi.slug ? 10 : isSponsored ? 9 : 7,
            color: '#fff',
            weight: isSponsored ? 3 : 2,
            fillColor: color,
            fillOpacity: 0.95,
        }).addTo(map);

        const label = isSponsored ? `★ ${poi.name}` : poi.name;
        marker.on('click', () => emit('select', poi));
        marker.bindTooltip(label, { direction: 'top', offset: [0, -8] });
        markers.push(marker);
    });
}

function highlightActive() {
    renderMarkers();
}

function flyTo(lat, lng) {
    map?.flyTo([lat, lng], 16, { duration: 0.8 });
}

defineExpose({ flyTo });
</script>

<template>
    <div class="relative h-full w-full" :class="class">
        <div ref="mapEl" class="h-full w-full" />
        <div class="absolute bottom-20 left-4 z-[400] flex gap-1 rounded-full bg-pg-surface/95 p-1 shadow-card backdrop-blur-sm lg:bottom-4">
            <button
                v-for="(cfg, key) in tileLayers"
                :key="key"
                type="button"
                class="rounded-full px-3 py-1.5 text-xs font-medium transition"
                :class="mapStyle === key ? 'bg-pg-primary text-white' : 'text-pg-muted hover:bg-gray-100'"
                @click="switchStyle(key)"
            >
                {{ cfg.label }}
            </button>
        </div>
    </div>
</template>
